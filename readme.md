### Build project:
- Docker build: docker-compose build
- Up: docker-compose up -d
- cd src && yarn install && yarn prod
### Tổng quan
- php artisan [ReportCommand] {fromDate} --force <br>
`Ví dụ: php artisan ReportWalletStatusProcess IndexActiveWallets 2022-11-30` <br>
`Chạy auto tự động resume ngày nếu process die : php artisan ReportWalletStatusProcess IndexActiveWallets auto`

### Thống kê ví ReportWalletStatusProcess
- Index ví đã kích hoạt
`php artisan ReportWalletStatusProcess IndexActiveWallets auto`

- Index ví đang hoạt động trong 12 tháng
  `php artisan ReportWalletStatusProcess IndexRecentActiveWallets auto`

- Index số dư ví tại 1 ngày trong quá khứ
  `php artisan ReportWalletStatusProcess IndexRecentActiveWallets auto`
  
- Chạy thống kê Main
	`php artisan ReportWalletStatusProcess MainProcess auto`
	
### Thống kê ReportTopCustomerProcess
`php artisan ReportTopCustomerProcess auto --force`

### Thống kê ReportTransactionStatusProcess
`php artisan ReportTransactionStatusProcess auto --force`

### Thống kê ReportTransactionTypesProcess
`php artisan ReportTransactionTypesProcess auto --force`
