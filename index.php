<?php
session_start();
if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background-color: #ffffff; /* สีพื้นหลังหลักเป็นสีขาว */
            color: #333333; /* สีตัวอักษรหลักเป็นสีเทาเข้ม */
        }
        .footer {
            background-color: #f8f9fa; /* สีเทาอ่อนสำหรับ Footer */
            padding: 20px 0;
            margin-top: auto;
            text-align: center;
        }
    </style>
</head>
<body>
    <!-- Navbar using Tailwind CSS -->
    <nav class="bg-gray-200 text-black shadow-lg"> <!-- เปลี่ยนพื้นหลัง Navbar เป็นสีเทา และตัวอักษรเป็นสีดำ -->
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <!-- Logo and Brand -->
                <div class="flex items-center">
                    <img src="https://lh5.googleusercontent.com/proxy/dFSvkaJ3s6GRq3Idd5YLpPVIKmOewgsaR0OrEg0-yXWnQO-HME3H4Yg8kRtfKPwD0UiIsObjAobdvx3bicht" 
                        alt="Logo" 
                        class="h-10 w-auto">
                    <a href="#" class="ml-2 text-xl font-bold">Dashboard</a>
                </div>

                <!-- Mobile Menu Button -->
                <div class="flex md:hidden">
                    <button type="button" class="text-black hover:text-gray-700 focus:outline-none"> <!-- เปลี่ยนสีไอคอนเมนูมือถือเป็นสีดำ -->
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>

                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center space-x-4">
                    <a href="index.php" class="text-black hover:text-gray-700 px-3 py-2 rounded-md text-sm font-medium">Main</a>
                    <a href="customer.php" class="text-black hover:text-gray-700 px-3 py-2 rounded-md text-sm font-medium">Customer</a>
                    <a href="bill.php" class="text-black hover:text-gray-700 px-3 py-2 rounded-md text-sm font-medium">Billing</a>
                    <a href="report.php" class="text-black hover:text-gray-700 px-3 py-2 rounded-md text-sm font-medium">Report</a>
                    <!-- Notify Icon -->
                    <a href="#" class="text-black hover:text-gray-700 px-3 py-2">
                        <i class="fas fa-bell"></i>
                    </a>
                    <!-- Logout Button -->
                    <a href="logout.php" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="relative bg-gray-900 text-yellow-500 py-24"> <!-- เปลี่ยนพื้นหลังเป็นสีเทาเข้มและข้อความเป็นสีเหลือง -->
        <div class="absolute inset-0 bg-black opacity-50"></div>
        <div class="relative container mx-auto px-4">
            <h1 class="text-4xl font-bold mb-4">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></h1>
            <p class="text-lg max-w-2xl">
                This is your dashboard. You can navigate using the menu above.
            </p>
        </div>
    </div>

    <!-- Timeline Section -->
    <div class="container mx-auto px-4 py-16">
        <div class="space-y-20">
            <!-- 2016 -->
            <div class="flex flex-col md:flex-row gap-8 items-start">
                <div class="w-full md:w-1/3">
                    <h2 class="text-2xl font-bold mb-2 text-gray-800">2016</h2> <!-- เปลี่ยนสีข้อความเป็นสีเทาเข้ม -->
                    <h3 class="text-xl text-gray-700 mb-4">Factory Office Collaboration</h3>
                    <p class="text-gray-600">
                        The company adhered to a customer-centric philosophy, implemented a quality management system, and maintained close communication with clients to provide reliable products and services.
                    </p>
                </div>
                <div class="w-full md:w-2/3">
                    <img src="/api/placeholder/800/400" alt="Office 2016" class="w-full h-64 object-cover rounded-lg shadow-lg">
                </div>
            </div>

            <!-- 2018 -->
            <div class="flex flex-col md:flex-row gap-8 items-start">
                <div class="w-full md:w-1/3">
                    <h2 class="text-2xl font-bold mb-2 text-gray-800">2018</h2> <!-- เปลี่ยนสีข้อความเป็นสีเทาเข้ม -->
                    <h3 class="text-xl text-gray-700 mb-4">Company Standardization</h3>
                    <p class="text-gray-600">
                        As the company grew, we continued to uphold the principles of integrity, innovation, professionalism, and efficiency, striving to provide even better services to our clients.
                    </p>
                </div>
                <div class="w-full md:w-2/3 grid grid-cols-2 gap-4">
                    <img src="/api/placeholder/400/300" alt="Meeting 2018" class="w-full h-48 object-cover rounded-lg shadow-lg">
                    <img src="/api/placeholder/400/300" alt="Team 2018" class="w-full h-48 object-cover rounded-lg shadow-lg">
                </div>
            </div>

            <!-- 2019 -->
            <div class="flex flex-col md:flex-row gap-8 items-start">
                <div class="w-full md:w-1/3">
                    <h2 class="text-2xl font-bold mb-2 text-gray-800">2019</h2> <!-- เปลี่ยนสีข้อความเป็นสีเทาเข้ม -->
                    <h3 class="text-xl text-gray-700 mb-4">Strict Quality Control</h3>
                    <p class="text-gray-600">
                        The company maintained a "quality first" approach, strictly controlling product quality and continuously improving production processes to ensure stable and reliable products.
                    </p>
                </div>
                <div class="w-full md:w-2/3">
                    <img src="/api/placeholder/800/400" alt="Quality Control 2019" class="w-full h-64 object-cover rounded-lg shadow-lg">
                </div>
            </div>

            <!-- 2020 -->
            <div class="flex flex-col md:flex-row gap-8 items-start">
                <div class="w-full md:w-1/3">
                    <h2 class="text-2xl font-bold mb-2 text-gray-800">2020</h2> <!-- เปลี่ยนสีข้อความเป็นสีเทาเข้ม -->
                    <h3 class="text-xl text-gray-700 mb-4">Expanding International Markets</h3>
                    <p class="text-gray-600">
                        With the global economy evolving, the company actively expanded into international markets, establishing strong partnerships with multiple countries and regions.
                    </p>
                </div>
                <div class="w-full md:w-2/3 grid grid-cols-2 gap-4">
                    <img src="/api/placeholder/400/300" alt="International Meeting 2020" class="w-full h-48 object-cover rounded-lg shadow-lg">
                    <img src="/api/placeholder/400/300" alt="Global Partners 2020" class="w-full h-48 object-cover rounded-lg shadow-lg">
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <span class="text-gray-800">© 2023 Your Company. All rights reserved.</span> <!-- เปลี่ยนสีข้อความ Footer เป็นสีเทาเข้ม -->
        </div>
    </footer>
</body>
</html>