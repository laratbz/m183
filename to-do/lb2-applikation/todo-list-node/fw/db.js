const mysql = require('mysql2/promise');
const dbConfig = require('../config');

async function connectDB() {
    return await mysql.createConnection(dbConfig);
}

// FIX: params support
async function executeStatement(statement, params = []) {
    const conn = await connectDB();
    const [results] = await conn.execute(statement, params);
    return results;
}

module.exports = {
    connectDB,
    executeStatement
};