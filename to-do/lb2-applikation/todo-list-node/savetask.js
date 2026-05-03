const db = require('./fw/db');

async function getHtml(req) {
    let html = '';
    let taskId = null;

    if (req.body.id && !isNaN(req.body.id)) {
        taskId = parseInt(req.body.id);

        let stmt = await db.executeStatement(
            'SELECT ID FROM tasks WHERE ID = ?',
            [taskId]
        );

        if (stmt.length === 0) {
            taskId = null;
        }
    }

    if (req.body.title && req.body.state){
        let state = req.body.state;
        let title = req.body.title;
        let userid = req.session.userid;

        if (!userid) {
            return "<span class='info info-error'>Unauthorized</span>";
        }

        if (taskId === null){
            await db.executeStatement(
                "INSERT INTO tasks (title, state, userID) VALUES (?, ?, ?)",
                [title, state, userid]
            );
        } else {
            await db.executeStatement(
                "UPDATE tasks SET title = ?, state = ? WHERE ID = ?",
                [title, state, taskId]
            );
        }

        html += "<span class='info info-success'>Update successful</span>";
    } else {
        html += "<span class='info info-error'>No update was made</span>";
    }

    return html;
}

module.exports = { html: getHtml };