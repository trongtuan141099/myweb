// Mở preview Ctrl + Shift + V
# Ghi chú tài liệu

## 1. Cập nhật tài liệu lên github

[Working Directory] --(git add)--> [Staging Area] --(git commit)--> [Local Repo] --(git push)--> [GitHub]
                                                                        ^                           |
                                                                        +-------(git pull)----------+
- Thiết lập git
  $ git config --global user.name "trongtuan141099"
  $ git config --global user.email trongtuan141099@gmail.com

- Khởi tạo kho chứa Git ẩn (.git):
  $ git init
  $ git remote add origin https://github.com/trongtuan141099/myweb.git

- Kiểm tra liên kết:
  $ git remote -v

- Upload code lên githut

  Xem trạng thái các file bị thay đổi/mới tạo:
  $ git status

  Đưa toàn bộ file thay đổi vào Staging Area (Sảnh chờ):
  $ git add . (hoặc git add <tên_file> để chọn từng file)

  Lưu lại điểm khôi phục vào Local Repo kèm mô tả:
  $ git commit -m "Khởi tạo dự án và thêm file cơ bản"

  Đẩy nhánh main lên GitHub (Lần đầu tiên dùng cờ -u để ghi nhớ nhánh):
  $ git push origin master

  Các lần push sau chỉ cần gõ:
  $ git push

### Lấy code về máy mới & Đồng bộ hàng ngày:Sử dụng trên máy tính thứ 2.
  Lần đầu trên Máy B: Tải toàn bộ kho chứa về:
  git clone <URL_REPOSITORY_CUA_BAN>
  
  Luôn kéo code mới nhất từ GitHub về trước khi sửa code:
  git pull


API (Application Programming Interface)
AJAX (Asynchronous JavaScript and XML)
