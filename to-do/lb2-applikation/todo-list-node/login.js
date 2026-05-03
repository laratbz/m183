const db = require('./fw/db');
const bcrypt = require('bcrypt');

async function handleLogin(req) {
    let msg = '';
    let user = { username: '', userid: 0, role: 'User' };

    const { username, password } = req.body;

    // 🔒 Input-Validierung
    if (!username || !password) {
        return { html: 'Missing credentials' + getHtml(), user };
    }

    const result = await validateLogin(username, password);

    if (result.valid) {
        user.username = username;
        user.userid = result.userId;
        user.role = result.role;
    } else {
        msg = result.msg;
    }

    return { html: msg + getHtml(), user };
}

function startUserSession(res, user, req) {
    req.session.userid = user.userid;
    req.session.username = user.username;
    req.session.role = user.role;

    res.redirect('/');
}

async function validateLogin(username, password) {
    let result = { valid: false, msg: '', userId: 0, role: 'User' };

    try {
        const dbConnection = await db.connectDB();

        // 🔥 KORREKTER JOIN für DEINE DB
        const sql = `
            SELECT u.ID, u.username, u.password, r.title as role
            FROM users u
                     JOIN permissions p ON u.ID = p.userID
                     JOIN roles r ON p.roleID = r.ID
            WHERE u.username = ?
        `;

        const [results] = await dbConnection.query(sql, [username]);

        if (results.length > 0) {
            let db_user = results[0];

            // 🔐 bcrypt Vergleich
            const match = await bcrypt.compare(password, db_user.password);

            if (match) {
                result.valid = true;
                result.userId = db_user.ID;       // ⚠️ GROSS geschrieben!
                result.role = db_user.role;       // kommt aus roles.title
            } else {
                result.msg = 'Incorrect password';
            }
        } else {
            result.msg = 'User not found';
        }

    } catch (err) {
        console.error("Login error:", err);
        result.msg = 'Internal server error';
    }

    return result;
}

function getHtml() {
    return `
    <h2>Login</h2>
    <form method="POST" action="/login">
        <div>
            <label>Username</label>
            <input type="text" name="username" required>
        </div>
        <div>
            <label>Password</label>
            <input type="password" name="password" required>
        </div>
        <button type="submit">Login</button>
    </form>
    `;
}

module.exports = { handleLogin, startUserSession, getHtml };