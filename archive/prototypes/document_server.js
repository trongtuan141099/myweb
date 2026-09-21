const express = require('express');
const cors = require('cors');
const multer = require('multer');
const path = require('path');
const fs = require('fs');

const app = express();
app.use(cors());
app.use(express.json());
app.use(express.static('public'));
app.use('/uploads', express.static('uploads'));

const DATA_FILE = path.join(__dirname, 'documents.json');

// Khởi tạo file chứa danh sách tài liệu nếu chưa có
if (!fs.existsSync(DATA_FILE)) {
    fs.writeFileSync(DATA_FILE, JSON.stringify([]));
}

// Cấu hình Multer nhận file PDF
const storage = multer.diskStorage({
    destination: (req, file, cb) => {
        if (!fs.existsSync('uploads')) fs.mkdirSync('uploads');
        cb(null, 'uploads/');
    },
    filename: (req, file, cb) => {
        cb(null, Date.now() + '-' + file.originalname);
    }
});
const upload = multer({ storage });

// API 1: Lấy danh sách tài liệu
app.get('/api/documents', (req, res) => {
    const data = JSON.parse(fs.readFileSync(DATA_FILE));
    res.json(data);
});

// API 2: Upload file và chỉ định thư mục lưu
app.post('/api/upload', upload.single('file'), (req, res) => {
    const { folder_id, doc_code, title } = req.body;
    const documents = JSON.parse(fs.readFileSync(DATA_FILE));

    const newDoc = {
        id: Date.now().toString(),
        doc_code: doc_code || ('DOC-' + Math.floor(Math.random() * 1000)),
        title: title || req.file.originalname,
        folder_id: folder_id, // Chỉ định thuộc thư mục nào
        file_path: `/uploads/${req.file.filename}`,
        status: 'Active'
    };

    documents.push(newDoc);
    fs.writeFileSync(DATA_FILE, JSON.stringify(documents, null, 2));
    res.json(newDoc);
});

// API 3: Xóa file
app.delete('/api/documents/:id', (req, res) => {
    let documents = JSON.parse(fs.readFileSync(DATA_FILE));
    documents = documents.filter(doc => doc.id !== req.params.id);
    fs.writeFileSync(DATA_FILE, JSON.stringify(documents, null, 2));
    res.json({ success: true });
});

app.listen(5000, () => console.log('Server đơn giản đang chạy tại http://localhost:5000'));