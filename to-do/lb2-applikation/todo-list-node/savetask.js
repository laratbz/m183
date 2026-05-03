const db = require('./fw/db');

async function getHtml(req) {
    const { id, title, state } = req.body;
    const userid = req.session.userid;

    if (!userid) {
        return "Unauthorized";
    }

    if (!title || title.trim() === '') {
        return "Invalid title";
    }

    let taskId = null;

    if (id && !isNaN(id)) {
        taskId = parseInt(id);
    }

    if (taskId === null) {
        await db.executeStatement(
            "INSERT INTO tasks (title, state, userID) VALUES (?, ?, ?)",
            [title, state, userid]
        );
    } else {
        // 🔒 FIX: userID prüfen!
        await db.executeStatement(
            "UPDATE tasks SET title = ?, state = ? WHERE ID = ? AND userID = ?",
            [title, state, taskId, userid]
        );
    }

    return "Saved successfully";
}

module.exports = { html: getHtml };