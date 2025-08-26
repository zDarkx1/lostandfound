<?php
require_once 'config/db.php';

$error = '';

if ($_POST) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        $stmt = $pdo->prepare("SELECT id, name, email, password FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            
            // Log the login
            $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, table_name, record_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user['id'], 'login', 'users', $user['id']]);
            
            header('Location: index.php');
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}

include 'partials/navbar.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Lost & Found</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/tailwind.css">
    <style>
        .glass-effect {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .floating-animation {
            animation: float 6s ease-in-out infinite;
        }
        
        .floating-animation:nth-child(2) {
            animation-delay: -2s;
        }
        
        .floating-animation:nth-child(3) {
            animation-delay: -4s;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-20px);
            }
        }
        
        .input-focus:focus {
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        .btn-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        .slide-in {
            animation: slideIn 0.8s ease-out forwards;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .pulse-glow {
            animation: pulseGlow 2s infinite;
        }
        
        @keyframes pulseGlow {
            0%, 100% {
                box-shadow: 0 0 20px rgba(167, 139, 250, 0.4);
            }
            50% {
                box-shadow: 0 0 40px rgba(167, 139, 250, 0.8);
            }
        }
    </style>
</head>
<body class="gradient-bg relative">
    <!-- Background decorative elements -->
    <div class="fixed inset-0 -z-10">
        <div class="floating-animation absolute top-20 left-10 w-32 h-32 bg-white bg-opacity-10 rounded-full blur-xl"></div>
        <div class="floating-animation absolute top-60 right-20 w-24 h-24 bg-purple-300 bg-opacity-20 rounded-full blur-lg"></div>
        <div class="floating-animation absolute bottom-40 left-1/4 w-40 h-40 bg-blue-300 bg-opacity-15 rounded-full blur-2xl"></div>
        <div class="floating-animation absolute bottom-20 right-1/3 w-28 h-28 bg-pink-300 bg-opacity-20 rounded-full blur-xl"></div>
    </div>
    
    <!-- Gradient overlay -->
    <div class="fixed inset-0 bg-gradient-to-br from-purple-600/20 via-blue-500/10 to-indigo-800/20 -z-10"></div>

    <div class="relative min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 z-10">
        <div class="max-w-md w-full space-y-8 slide-in">
            <!-- Logo and Header -->
            <div class="text-center">
                <div class="flex justify-center mb-8">
                    <div class="w-20 h-20 bg-gradient-to-br from-purple-600 to-blue-600 rounded-2xl flex items-center justify-center shadow-2xl pulse-glow transform hover:scale-105 transition-all duration-300">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>
                <h1 class="text-4xl font-bold text-white mb-2 tracking-tight">Welcome Back</h1>
                <p class="text-lg text-white/80 font-medium">
                    Sign in to continue to Lost & Found
                </p>
            </div>

            <!-- Login Card -->
            <div class="glass-effect rounded-3xl p-8 shadow-2xl backdrop-blur-lg border border-white/20">
                <?php if ($error): ?>
                    <div class="mb-6 bg-red-500/20 backdrop-blur-sm border border-red-300/30 text-red-100 px-4 py-3 rounded-xl flex items-center space-x-3">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6">
                    <!-- Email Input -->
                    <div class="space-y-2">
                        <label for="email" class="block text-sm font-semibold text-white/90">Email Address</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                                </svg>
                            </div>
                            <input id="email" name="email" type="email" required 
                                   class="input-focus w-full pl-12 pr-4 py-4 bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl text-white placeholder-white/60 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:border-transparent transition-all duration-300"
                                   placeholder="Enter your email address"
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>
                    </div>

                    <!-- Password Input -->
                    <div class="space-y-2">
                        <label for="password" class="block text-sm font-semibold text-white/90">Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                            </div>
                            <input id="password" name="password" type="password" required 
                                   class="input-focus w-full pl-12 pr-4 py-4 bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl text-white placeholder-white/60 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:border-transparent transition-all duration-300"
                                   placeholder="Enter your password">
                        </div>
                    </div>

                    <!-- Remember Me & Forgot Password -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input id="remember-me" name="remember-me" type="checkbox" 
                                   class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-white/30 rounded bg-white/10 backdrop-blur-sm">
                            <label for="remember-me" class="ml-3 block text-sm text-white/80 font-medium">
                                Remember me
                            </label>
                        </div>

                        <div class="text-sm">
                            <a href="#" class="font-semibold text-purple-200 hover:text-white transition-colors duration-200">
                                Forgot password?
                            </a>
                        </div>
                    </div>

                    <!-- Sign In Button -->
                    <button type="submit" class="btn-hover w-full py-4 px-6 bg-gradient-to-r from-purple-600 to-blue-600 text-white font-bold text-lg rounded-xl shadow-lg transition-all duration-300 focus:outline-none focus:ring-4 focus:ring-purple-500/50">
                        <span class="flex items-center justify-center space-x-3">
                            <span>Sign In</span>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                            </svg>
                        </span>
                    </button>
                </form>

                <!-- Divider -->
                <div class="mt-8">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-white/20"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-4 bg-white/10 backdrop-blur-sm text-white/70 rounded-full">Or continue with</span>
                        </div>
                    </div>

                    <!-- Social Login Options -->
                    <div class="mt-6 grid grid-cols-2 gap-4">
                        <button class="btn-hover flex items-center justify-center px-4 py-3 bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl text-white font-medium transition-all duration-300 hover:bg-white/20">
                            <svg class="w-5 h-5 mr-3" viewBox="0 0 24 24">
                                <path fill="currentColor" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                <path fill="currentColor" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                <path fill="currentColor" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                <path fill="currentColor" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                            </svg>
                            Google
                        </button>
                        <button class="btn-hover flex items-center justify-center px-4 py-3 bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl text-white font-medium transition-all duration-300 hover:bg-white/20">
                            <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                            Facebook
                        </button>
                    </div>
                </div>

                <!-- Sign Up Link -->
                <div class="mt-8 text-center">
                    <p class="text-white/70">
                        Don't have an account? 
                        <a href="register.php" class="font-semibold text-purple-200 hover:text-white transition-colors duration-200 ml-1">
                            Create one here
                        </a>
                    </p>
                </div>
            </div>

            <!-- Additional Info -->
            <div class="text-center">
                <p class="text-white/60 text-sm">
                    By signing in, you agree to our 
                    <a href="#" class="text-purple-200 hover:text-white transition-colors">Terms of Service</a> 
                    and 
                    <a href="#" class="text-purple-200 hover:text-white transition-colors">Privacy Policy</a>
                </p>
            </div>
        </div>
    </div>

    <?php include 'partials/footer.php'; ?>
</body>
</html>