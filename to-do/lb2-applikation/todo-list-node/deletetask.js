const db = require('./fw/db');

async function getHtml(req) {
    const id = req.query.id;
    const userid = req.session.userid;

    if (!userid) return "Unauthorized";
    if (!id) return "Missing ID";

    await db.executeStatement(
        "DELETE FROM tasks WHERE ID = ? AND userID = ?",
        [id, userid]
    );

    return `
        <h2>Deleted successfully</h2>
        <a href="/">Back</a>
    `;
}

module.exports = { html: getHtml };