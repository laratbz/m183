const db = require('./fw/db');
const bcrypt = require('bcrypt');

async function handleLogin(req) {
    let msg = '';

    let user = {
        username: '',
        userid: 0,
        role: 'User'
    };

    const { username, password } = req.body;

    // 🔒 Validation
    if (!username || !password) {
        return {
            html: 'Missing credentials<br>' + getHtml(),
            user
        };
    }

    const result = await validateLogin(username, password);

    if (result.valid) {
        user.username = username;
        user.userid = result.userId;
        user.role = result.role;
    } else {
        msg = result.msg;
    }

    return {
        html: msg + getHtml(),
        user
    };
}

/**
 * ✅ FIXED: NO res here anymore
 */
function startUserSession(req, user) {
    req.session.userid = user.userid;
    req.session.username = user.username;
    req.session.role = user.role;
}

async function validateLogin(username, password) {
    let result = {
        valid: false,
        msg: '',
        userId: 0,
        role: 'User'
    };

    let dbConnection;

    try {
        dbConnection = await db.connectDB();

        const sql = `
            SELECT u.ID, u.username, u.password, r.title AS role
            FROM users u
            JOIN permissions p ON u.ID = p.userID
            JOIN roles r ON p.roleID = r.ID
            WHERE u.username = ?
        `;

        const [results] = await dbConnection.query(sql, [username]);

        if (results.length === 0) {
            result.msg = 'User not found';
            return result;
        }

        const dbUser = results[0];

        const match = await bcrypt.compare(password, dbUser.password);

        if (!match) {
            result.msg = 'Incorrect password';
            return result;
        }

        result.valid = true;
        result.userId = dbUser.ID;
        result.role = dbUser.role;

    } catch (err) {
        console.error("Login error:", err);
        result.msg = 'Internal server error';
    } finally {
        if (dbConnection) {
            try {
                await dbConnection.end();
            } catch (e) {
                console.error("DB close error:", e);
            }
        }
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

module.exports = {
    handleLogin,
    startUserSession,
    getHtml
};