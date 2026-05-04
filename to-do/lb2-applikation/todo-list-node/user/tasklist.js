const db = require('../fw/db');

async function getHtml(req) {
    const userid = req.session.userid;

    if (!userid) {
        return "Unauthorized";
    }

    let html = `
    <section id="list">
        <a href="/edit">Create Task</a>
        <table>
            <tr>
                <th>ID</th>
                <th>Description</th>
                <th>State</th>
                <th></th>
            </tr>
    `;

    let conn = await db.connectDB();

    let [result] = await conn.query(
        'SELECT ID, title, state FROM tasks WHERE userID = ?',
        [userid]
    );

    result.forEach(row => {
        html += `
            <tr>
                <td>${row.ID}</td>
                <td class="wide">${row.title}</td>
                <td>${ucfirst(row.state)}</td>
                <td>
                    <a href="/edit?id=${row.ID}">edit</a> |
                    <a href="/delete?id=${row.ID}">delete</a>
                </td>
            </tr>
        `;
    });

    html += `
        </table>
    </section>`;

    return html;
}

function ucfirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

module.exports = { html: getHtml };