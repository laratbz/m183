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

const editTask = require('./edit');
const deleteTask = require('./deletetask');

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

// HELPERS
function activeUserSession(req) {
    return req.session && req.session.userid;
}

function isAdmin(req) {
    return req.session && req.session.role === 'Admin';
}

// HOME
app.get('/', async (req, res) => {
    if (!activeUserSession(req)) return res.redirect('/login');

    let html = await wrapContent(await index.html(req), req);
    return res.send(html);
});

// LOGIN
app.get('/login', async (req, res) => {
    let html = await wrapContent(login.getHtml(), req);
    return res.send(html);
});

app.post('/login', async (req, res) => {
    let result = await login.handleLogin(req);

    if (result.user.userid !== 0) {
        login.startUserSession(req, result.user); // ✅ FIX
        return res.redirect('/');
    }

    let html = await wrapContent(result.html, req);
    return res.send(html);
});

// LOGOUT
app.get('/logout', (req, res) => {
    req.session.destroy(() => {
        res.redirect('/login');
    });
});

// ADMIN
app.get('/admin/users', async (req, res) => {
    if (!activeUserSession(req) || !isAdmin(req)) {
        return res.status(403).send('Forbidden');
    }

    let html = await wrapContent(await adminUser.html, req);
    return res.send(html);
});

// EDIT
app.get('/edit', async (req, res) => {
    if (!activeUserSession(req)) return res.redirect('/login');

    let html = await wrapContent(await editTask.html(req), req);
    return res.send(html);
});

// SAVE
app.post('/savetask', async (req, res) => {
    if (!activeUserSession(req)) return res.redirect('/login');

    let html = await wrapContent(await saveTask.html(req), req);
    return res.send(html);
});

// DELETE
app.get('/delete', async (req, res) => {
    if (!activeUserSession(req)) return res.redirect('/login');

    let html = await wrapContent(await deleteTask.html(req), req);
    return res.send(html);
});

// SEARCH
app.post('/search', async (req, res) => {
    if (!activeUserSession(req)) return res.redirect('/login');

    let html = await search.html(req);
    return res.send(html);
});

// START
app.listen(PORT, () => {
    console.log(`Server läuft auf http://localhost:${PORT}`);
});

// WRAPPER
async function wrapContent(content, req) {
    let headerHtml = await header(req);
    return headerHtml + content + footer;
}