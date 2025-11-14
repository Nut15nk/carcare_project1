<!-- components/Footer.php -->
<!-- อัปเดตเป็นเวอร์ชัน PHP ที่แปลงจาก React TSX -->
<footer class="bg-gray-900 text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <!-- Company Info -->
            <div class="col-span-1 md:col-span-2">
                <h3 class="text-xl font-bold mb-4">ร้านมอเตอร์ไซค์ให้เช่า เทมป์เทชัน</h3>
                <p class="text-gray-300 mb-4">
                    ให้บริการเช่ารถจักรยานยนต์คุณภาพ ครบครันทุกรุ่น ตั้งแต่ 110cc ถึง 700cc 
                    พร้อมบริการส่งรถถึงโรงแรมและคืนรถที่สนามบินหาดใหญ่
                </p>
                <div class="flex space-x-4">
                    <a href="#" class="text-gray-300 hover:text-blue-400 transition-colors">
                        <!-- แปลง <Facebook> เป็น <i data-lucide="..."> -->
                        <i data-lucide="facebook" class="h-6 w-6"></i>
                    </a>
                    <a href="#" class="text-gray-300 hover:text-green-400 transition-colors">
                        <i data-lucide="message-circle" class="h-6 w-6"></i>
                    </a>
                </div>
            </div>

            <!-- Contact Info -->
            <div>
                <h4 class="text-lg font-semibold mb-4">ติดต่อเรา</h4>
                <div class="space-y-3">
                    <div class="flex items-start space-x-3">
                        <i data-lucide="map-pin" class="h-5 w-5 text-blue-400 mt-1 flex-shrink-0"></i>
                        <div>
                            <p class="text-gray-300">31/4 ซอยศรีสุดา</p>
                            <p class="text-gray-300">ตำบลบ่อยาง อำเภอเมืองสงขลา</p>
                            <p class="text-gray-300">จังหวัดสงขลา</p>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-3">
                        <i data-lucide="phone" class="h-5 w-5 text-blue-400"></i>
                        <span class="text-gray-300">074-123-456</span>
                    </div>
                    
                    <div class="flex items-center space-x-3">
                        <i data-lucide="mail" class="h-5 w-5 text-blue-400"></i>
                        <span class="text-gray-300">info@temptation.com</span>
                    </div>
                </div>
            </div>

            <!-- Business Hours -->
            <div>
                <h4 class="text-lg font-semibold mb-4">เวลาทำการ</h4>
                <div class="space-y-2">
                    <div class="flex items-center space-x-3">
                        <i data-lucide="clock" class="h-5 w-5 text-blue-400"></i>
                        <div>
                            <p class="text-gray-300">ทุกวัน</p>
                            <p class="text-gray-300">08:00 - 20:00 น.</p>
                        </div>
                    </div>
                </div>

                <div class="mt-6">
                    <h5 class="font-semibold mb-2">บริการพิเศษ</h5>
                    <ul class="text-sm text-gray-300 space-y-1">
                        <li>• บริการส่งรถถึงโรงแรม</li>
                        <li>• คืนรถที่สนามบินหาดใหญ่</li>
                        <li>• ประกันภัยครอบคลุม</li>
                        <li>• บริการ 24 ชั่วโมง</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="border-t border-gray-800 mt-8 pt-8">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <p class="text-gray-400 text-sm">
                    <!-- แปลง © 2024 เป็น PHP date('Y') เพื่อให้เป็นปีปัจจุบันเสมอ -->
                    © <?php echo date('Y'); ?> ร้านมอเตอร์ไซค์ให้เช่า เทมป์เทชัน. สงวนลิขสิทธิ์.
                </p>
                <div class="flex space-x-6 mt-4 md:mt-0">
                    <a href="#" class="text-gray-400 hover:text-white text-sm transition-colors">
                        นโยบายความเป็นส่วนตัว
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white text-sm transition-colors">
                        เงื่อนไขการใช้งาน
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white text-sm transition-colors">
                        คำถามที่พบบ่อย
                    </a>
                </div>
            </div>
        </div>
    </div>
</footer>