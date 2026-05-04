const db = require('./fw/db');

async function getHtml(req) {
    const { id, title, state } = req.body;
    const userid = req.session.userid;

    if (!userid) return "Unauthorized";
    if (!title) return "Invalid title";

    if (!id) {
        await db.executeStatement(
            "INSERT INTO tasks (title, state, userID) VALUES (?, ?, ?)",
            [title, state, userid]
        );
    } else {
        await db.executeStatement(
            "UPDATE tasks SET title = ?, state = ? WHERE ID = ? AND userID = ?",
            [title, state, id, userid]
        );
    }

    return `<h2>Saved successfully</h2><a href="/">Back</a>`;
}

module.exports = { html: getHtml };