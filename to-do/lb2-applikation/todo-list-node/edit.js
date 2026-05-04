const db = require('./fw/db');

async function getHtml(req) {
    const userid = req.session.userid;
    if (!userid) return "Unauthorized";

    let taskId = req.query.id || '';
    let title = '';
    let state = '';

    const options = ['open', 'in progress', 'done'];

    if (taskId) {
        const conn = await db.connectDB();

        const [result] = await conn.execute(
            'SELECT ID, title, state FROM tasks WHERE ID = ? AND userID = ?',
            [taskId, userid]
        );

        if (result.length > 0) {
            title = result[0].title;
            state = result[0].state;
        }
    }

    let html = `
    <h1>${taskId ? "Edit Task" : "Create Task"}</h1>

    <form method="post" action="/savetask">
        <input type="hidden" name="id" value="${taskId || ''}">

        <div>
            <label>Description</label>
            <input type="text" name="title" value="${title}">
        </div>

        <div>
            <label>State</label>
            <select name="state">
    `;

    for (let opt of options) {
        let selected = state === opt ? 'selected' : '';
        html += `<option value="${opt}" ${selected}>${opt}</option>`;
    }

    html += `
            </select>
        </div>

        <button type="submit">Save</button>
    </form>
    `;

    return html;
}

module.exports = { html: getHtml };