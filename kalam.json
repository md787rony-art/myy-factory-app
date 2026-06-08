const FILE_NAME = "kalam.json";

// ১০-১২ জন মিস্ত্রির ইউজারনেম, পাসওয়ার্ড, নির্দিষ্ট টাইটেল এবং হাজিরার ফিক্সড রেট ডাটাবেস
const INITIAL_DATA = {
  "users": [
    {"username": "kamal", "password": "123", "title": "কালার মিস্ত্রি", "hajira_rate": 800},
    {"username": "rahim", "password": "234", "title": "কাঠমিস্ত্রি", "hajira_rate": 900},
    {"username": "babul", "password": "345", "title": "কন্ট্রাক্টদার", "hajira_rate": 0},
    {"username": "selim", "password": "456", "title": "হাজিরা মিস্ত্রি", "hajira_rate": 750},
    {"username": "alamin", "password": "567", "title": "CNC নকশা দোকান", "hajira_rate": 0},
    {"username": "jasim", "password": "678", "title": "কালার মিস্ত্রি", "hajira_rate": 850},
    {"username": "korim", "password": "789", "title": "কাঠমিস্ত্রি", "hajira_rate": 950},
    {"username": "monir", "password": "012", "title": "কন্ট্রাক্টদার", "hajira_rate": 0},
    {"username": "biplob", "password": "321", "title": "হাজিরা মিস্ত্রি", "hajira_rate": 700},
    {"username": "sohel", "password": "432", "title": "CNC নকশা দোকান", "hajira_rate": 0}
  ],
  "ledger": []
};

function getDatabaseFile() {
  const files = DriveApp.getFilesByName(FILE_NAME);
  if (files.hasNext()) {
    return files.next();
  } else {
    return DriveApp.createFile(FILE_NAME, JSON.stringify(INITIAL_DATA), MimeType.PLAIN_TEXT);
  }
}

function doGet(e) {
  const action = e.parameter.action;
  const file = getDatabaseFile();
  const db = JSON.parse(file.getBlob().getDataAsString());

  if (action === 'login') {
    const u = e.parameter.username;
    const p = e.parameter.password;
    const user = db.users.find(user => user.username === u && user.password === p);

    if (user) {
      let totalEarned = 0, totalPaid = 0, totalHajira = 0, history = [];
      db.ledger.forEach(item => {
        if (item.username === u && item.status === 'Approved') {
          totalEarned += parseFloat(item.amount || 0);
          totalPaid += parseFloat(item.paid_amount || 0);
          totalHajira += parseFloat(item.hajira_count || 0);
          history.push(item);
        }
      });

      return ContentService.createTextOutput(JSON.stringify({
        "status": "SUCCESS", "name": user.username, "title": user.title, "rate": user.hajira_rate,
        "totalEarned": totalEarned, "totalPaid": totalPaid, "totalHajira": totalHajira, "history": history
      })).setMimeType(ContentService.MimeType.JSON);
    }
    return ContentService.createTextOutput(JSON.stringify({"status": "FAILED"})).setMimeType(ContentService.MimeType.JSON);
  }

  if (action === 'get_pending') {
    const pending = db.ledger.filter(item => item.status === 'Pending');
    return ContentService.createTextOutput(JSON.stringify(pending)).setMimeType(ContentService.MimeType.JSON);
  }
}

function doPost(e) {
  const action = e.parameter.action;
  const file = getDatabaseFile();
  const db = JSON.parse(file.getBlob().getDataAsString());

  if (action === 'submit_report') {
    const d = new Date();
    const months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
    
    const newReport = {
      "id": "REP_" + d.getTime(),
      "username": e.parameter.username,
      "date": d.getDate() + "-" + months[d.getMonth()] + "-" + d.getFullYear(),
      "time": d.toLocaleTimeString(),
      "report_type": e.parameter.report_type,
      "details": e.parameter.details,
      "amount": parseFloat(e.parameter.amount || 0),
      "paid_amount": 0,
      "hajira_count": parseFloat(e.parameter.hajira_count || 0),
      "image_path": e.parameter.image_path,
      "status": "Pending"
    };

    db.ledger.push(newReport);
    file.setContent(JSON.stringify(db));
    return ContentService.createTextOutput(JSON.stringify({"status": "SUCCESS"})).setMimeType(ContentService.MimeType.JSON);
  }

  if (action === 'update_status') {
    const id = e.parameter.id;
    const status = e.parameter.status;

    db.ledger.forEach(item => {
      if (item.id === id) {
        item.status = status;
      }
    });

    file.setContent(JSON.stringify(db));
    return ContentService.createTextOutput(JSON.stringify({"status": "SUCCESS"})).setMimeType(ContentService.MimeType.JSON);
  }
}
