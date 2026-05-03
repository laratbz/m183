const express = require('express');
const session = require('express-session');
const cookieParser = require('cookie-parser');
const path = require('path');

const header = require('./fw/header');
const footer = require('./fw/footer');
const login = require('./login');
const index = require('./index');
const adminUser = require('./admin/users');
const saveTask = require('./savetask');
const search = require('./search');

const app = express();
const PORT = 3000;

app.use(session({
    secret: 'change_this_secret',
    resave: false,
    saveUninitialized: false,
    cookie: {
        httpOnly: true,
        secure: false,
        sameSite: 'strict'
    }
}));

app.use(express.urlencoded({ extended: true }));
app.use(express.json());
app.use(express.static(path.join(__dirname, 'public')));
app.use(cookieParser());

function activeUserSession(req) {
    return req.session && req.session.userid;
}

function isAdmin(req) {
    return req.session && req.session.role === 'Admin'; // 🔥 DB liefert "Admin"
}

// ROUTES
app.get('/', async (req, res) => {
    if (!activeUserSession(req)) return res.redirect('/login');

    let html = await wrapContent(await index.html(req), req);
    res.send(html);
});

// LOGIN
app.get('/login', async (req, res) => {
    let html = await wrapContent(login.getHtml(), req);
    res.send(html);
});

app.post('/login', async (req, res) => {
    let result = await login.handleLogin(req);

    if (result.user.userid !== 0) {
        login.startUserSession(res, result.user, req);
    } else {
        let html = await wrapContent(result.html, req);
        res.send(html);
    }
});

app.get('/logout', (req, res) => {
    req.session.destroy();
    res.redirect('/login');
});

// 🔥 ADMIN FIX
app.get('/admin/users', async (req, res) => {
    if (activeUserSession(req) && isAdmin(req)) {
        let html = await wrapContent(await adminUser.html, req);
        res.send(html);
    } else {
        res.status(403).send('Forbidden');
    }
});

// TASK
app.post('/savetask', async (req, res) => {
    if (!activeUserSession(req)) return res.redirect('/login');

    let html = await wrapContent(await saveTask.html(req), req);
    res.send(html);
});

// SEARCH
app.post('/search', async (req, res) => {
    if (!activeUserSession(req)) return res.redirect('/login');

    let html = await search.html(req);
    res.send(html);
});

app.listen(PORT, () => {
    console.log(`Server läuft auf http://localhost:${PORT}`);
});

async function wrapContent(content, req) {
    let headerHtml = await header(req);
    return headerHtml + content + footer;
}