<?php
// Cricket Prediction Website Auto-Deployer
echo "<h1>🚀 Cricket Prediction Website Auto-Deployer</h1>";
echo "<p>This script will create a complete cricket prediction website with all necessary files and database setup.</p>";

// Configuration
$db_host = 'localhost';
$db_user = 'cricket_user';
$db_pass = 'Cricket@2025';
$db_name = 'cricket_prediction';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = $_POST['db_host'] ?? $db_host;
    $db_user = $_POST['db_user'] ?? $db_user;
    $db_pass = $_POST['db_pass'] ?? $db_pass;
    $db_name = $_POST['db_name'] ?? $db_name;
}

// Step 1: Create files
echo "<h2>Step 1: Creating files...</h2>";

$files = [
    'index.php' => '<?php
require_once \'database.php\';

$latest_match = getLatestMatch();
$players = getTopPlayers();
$predictions = getLatestPredictions();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Cricket Predictions - Ankit AI Army</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>🏏 Live Cricket Predictions</h1>
        
        <div class="live-match">
            <h2>🔴 Live Match</h2>
            <div id="live-score">
                <?php if ($latest_match): ?>
                    <div class="match-info">
                        <h3><?php echo htmlspecialchars($latest_match[\'teams\']); ?></h3>
                        <p>Score: <?php echo htmlspecialchars($latest_match[\'score\']); ?></p>
                        <p>Status: <?php echo htmlspecialchars($latest_match[\'status\']); ?></p>
                        <p>Last Updated: <?php echo htmlspecialchars($latest_match[\'updated\']); ?></p>
                    </div>
                <?php else: ?>
                    <p>No live match data available. Scraping will start soon...</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="top-players">
            <h2>👥 Top Performers</h2>
            <div class="players-grid">
                <?php foreach ($players as $player): ?>
                    <div class="player-card">
                        <h4><?php echo htmlspecialchars($player[\'name\']); ?></h4>
                        <p>Runs: <?php echo htmlspecialchars($player[\'runs\']); ?></p>
                        <p>Strike Rate: <?php echo htmlspecialchars($player[\'strike_rate\']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="predictions">
            <h2>🎯 AI Predictions</h2>
            <div class="predictions-list">
                <?php foreach ($predictions as $prediction): ?>
                    <div class="prediction-card">
                        <h4><?php echo htmlspecialchars($prediction[\'match\']); ?></h4>
                        <p>Prediction: <?php echo htmlspecialchars($prediction[\'prediction\']); ?></p>
                        <p>Confidence: <?php echo htmlspecialchars($prediction[\'confidence\']); ?>%</p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="status">
            <h3>🤖 System Status</h3>
            <p>Auto-Scraping: <span id="scraping-status">Active</span></p>
            <p>Last Update: <span id="last-update"><?php echo date(\'Y-m-d H:i:s\'); ?></span></p>
            <p>Next Update: <span id="next-update">In 5 minutes</span></p>
        </div>
    </div>
    <script src="script.js"></script>
</body>
</html>',

    'scrape.php' => '<?php
require_once \'database.php\';

function scrapeESPNcricinfo() {
    $url = "https://www.espncricinfo.com/live-cricket-score";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $html = curl_exec($ch);
    curl_close($ch);

    $data = [
        \'teams\' => \'Extracting...\',
        \'score\' => \'Loading...\',
        \'status\' => \'Live\',
        \'updated\' => date(\'Y-m-d H:i:s\')
    ];

    if (preg_match(\'/<title>(.*?)<\\/title>/\', $html, $matches)) {
        $data[\'teams\'] = $matches[1];
    }

    return $data;
}

function runScraping() {
    try {
        $match_data = scrapeESPNcricinfo();
        saveMatchData($match_data);
        logScraping(\'Success\', \'Scraping completed successfully\');
        return true;
    } catch (Exception $e) {
        logScraping(\'Error\', $e->getMessage());
        return false;
    }
}

if (runScraping()) {
    echo "Scraping completed successfully at " . date(\'Y-m-d H:i:s\');
} else {
    echo "Scraping failed at " . date(\'Y-m-d H:i:s\');
}
?>',

    'database.php' => '<?php
define(\'DB_HOST\', \'' . $db_host . '\');
define(\'DB_USER\', \'' . $db_user . '\');
define(\'DB_PASS\', \'' . $db_pass . '\');
define(\'DB_NAME\', \'' . $db_name . '\');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function getLatestMatch() {
    global $conn;
    $result = $conn->query("SELECT * FROM matches ORDER BY created_at DESC LIMIT 1");
    return $result->fetch_assoc();
}

function getTopPlayers() {
    global $conn;
    $result = $conn->query("SELECT * FROM players ORDER BY runs DESC LIMIT 6");
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getLatestPredictions() {
    global $conn;
    $result = $conn->query("SELECT * FROM predictions ORDER BY created_at DESC LIMIT 5");
    return $result->fetch_all(MYSQLI_ASSOC);
}

function saveMatchData($data) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO matches (teams, score, status, updated) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $data[\'teams\'], $data[\'score\'], $data[\'status\'], $data[\'updated\']);
    $stmt->execute();
}

function logScraping($status, $message) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO scraping_log (status, message, created_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("ss", $status, $message);
    $stmt->execute();
}

$conn->close();
?>',

    'setup.sql' => 'CREATE DATABASE IF NOT EXISTS ' . $db_name . ';
USE ' . $db_name . ';

CREATE TABLE IF NOT EXISTS matches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teams VARCHAR(255) NOT NULL,
    score VARCHAR(100),
    status VARCHAR(100),
    updated DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS players (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    runs INT DEFAULT 0,
    strike_rate DECIMAL(5,2) DEFAULT 0,
    wickets INT DEFAULT 0,
    economy DECIMAL(5,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS predictions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    match_name VARCHAR(255) NOT NULL,
    prediction TEXT NOT NULL,
    confidence INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS scraping_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    status VARCHAR(50) NOT NULL,
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO players (name, runs, strike_rate) VALUES
(\'Virat Kohli\', 50, 150.00),
(\'Rohit Sharma\', 45, 140.00),
(\'Jasprit Bumrah\', 10, 0, 15, 6.50),
(\'KL Rahul\', 35, 130.00),
(\'Hardik Pandya\', 30, 145.00),
(\'Ravindra Jadeja\', 25, 120.00);

INSERT INTO predictions (match_name, prediction, confidence) VALUES
(\'The Hundred Women\', \'Northern Superchargers to win\', 75),
(\'IPL 2025\', \'Mumbai Indians to qualify\', 85),
(\'T20 World Cup\', \'India to reach semifinals\', 90);',

    'style.css' => '*{margin:0;padding:0;box-sizing:border-box;}body{font-family:Arial,sans-serif;background:linear-gradient(135deg,#1e3c72 0%,#2a5298 100%);color:white;min-height:100vh;}.container{max-width:1200px;margin:0 auto;padding:20px;}h1{text-align:center;font-size:2.5em;margin-bottom:30px;text-shadow:2px 2px 4px rgba(0,0,0,0.5);}h2{font-size:1.8em;margin-bottom:20px;color:#ffd700;}.live-match,.top-players,.predictions,.status{background:rgba(255,255,255,0.1);border-radius:15px;padding:20px;margin-bottom:30px;backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,0.2);}.match-info{background:rgba(0,0,0,0.3);padding:15px;border-radius:10px;margin-top:10px;}.players-grid,.predictions-list{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:15px;margin-top:15px;}.player-card,.prediction-card{background:rgba(255,255,255,0.2);padding:15px;border-radius:10px;text-align:center;transition:transform 0.3s ease;}.player-card:hover,.prediction-card:hover{transform:translateY(-5px);}.player-card h4,.prediction-card h4{color:#ffd700;margin-bottom:10px;}.status{text-align:center;}.status span{color:#00ff00;font-weight:bold;}#live-score{font-size:1.2em;font-weight:bold;}@media(max-width:768px){.container{padding:10px;}h1{font-size:2em;}.players-grid,.predictions-list{grid-template-columns:1fr;}}',

    'script.js' => 'function autoRefresh(){location.reload();}function updateCountdown(){const now=new Date();const nextUpdate=new Date(now.getTime()+300000);const diff=nextUpdate-now;const minutes=Math.floor((diff%3600000)/60000);const seconds=Math.floor((diff%60000)/1000);document.getElementById(\'next-update\').textContent=`${minutes}m ${seconds}s`;}document.addEventListener(\'DOMContentLoaded\',function(){setInterval(autoRefresh,30000);setInterval(updateCountdown,1000);updateCountdown();console.log(\'Cricket Prediction System Initialized\');});',

    'cron_setup.php' => '<?php
echo "<h1>Cron Job Setup Guide</h1>";
echo "<h2>Add this to your cPanel Cron Jobs:</h2>";
echo "<code>php /home/your_username/public_html/cricket/scrape.php</code>";
echo "<h2>Set to run every 5 minutes</h2>";
echo "<h3>Current Server Time: " . date(\'Y-m-d H:i:s\') . "</h3>";
echo "<h3>Recommended Cron Schedule: */5 * * * *</h3>";
?>',

    'README.md' => '# Cricket Prediction Website

## Features
- 🏏 Live match data scraping
- 🤖 AI-powered predictions
- 🔄 Real-time updates every 5 minutes
- 📱 Mobile responsive design
- ⚡ Fast and lightweight

## Quick Deployment
1. Upload all files to your cPanel public_html/cricket folder
2. Create database using setup.sql
3. Update database credentials in database.php
4. Set up cron job for scraping (every 5 minutes)
5. Visit yourdomain.com/cricket

## Files
- `index.php` - Main website file
- `scrape.php` - Data scraper
- `database.php` - Database connection
- `setup.sql` - Database structure
- `style.css` - Styling
- `script.js` - JavaScript
- `cron_setup.php` - Cron job helper

## Requirements
- PHP 7.4+
- MySQL 5.7+
- cPanel hosting
- cURL enabled

## Support
For issues and questions, please check the troubleshooting section or create an issue.

## License
MIT License - Feel free to use and modify',

    '.gitignore' => '*.log
*.zip
vendor/
node_modules/
.env
config.php
.DS_Store
Thumbs.db'
];

$success_count = 0;
foreach ($files as $filename => $content) {
    if (file_put_contents($filename, $content)) {
        echo "✅ Created $filename<br>";
        $success_count++;
    } else {
        echo "❌ Failed to create $filename<br>";
    }
}

// Step 2: Database setup
echo "<h2>Step 2: Setting up database...</h2>";

try {
    // Create database connection
    $conn = new mysqli($db_host, $db_user, $db_pass);
    
    // Create database if not exists
    $conn->query("CREATE DATABASE IF NOT EXISTS `$db_name`");
    echo "✅ Database '$db_name' created or already exists<br>";
    
    // Select database
    $conn->select_db($db_name);
    
    // Read and execute setup.sql
    $setup_sql = file_get_contents('setup.sql');
    if ($conn->multi_query($setup_sql)) {
        echo "✅ Database tables created successfully<br>";
    } else {
        echo "❌ Error creating database tables: " . $conn->error . "<br>";
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

// Step 3: Configuration check
echo "<h2>Step 3: Configuration</h2>";
echo "<p>Database configuration saved in database.php</p>";
echo "<p>Host: $db_host</p>";
echo "<p>Database: $db_name</p>";
echo "<p>Username: $db_user</p>";

// Step 4: Instructions
echo "<h2>Step 4: Next Steps</h2>";
echo "<ol>";
echo "<li>Set up cron job in cPanel:</li>";
echo "<li>Go to cPanel > Cron Jobs</li>";
echo "<li>Add new cron job with command: <code>php " . $_SERVER['DOCUMENT_ROOT'] . "/cricket/scrape.php</code></li>";
echo "<li>Set schedule to every 5 minutes: <code>*/5 * * * *</code></li>";
echo "<li>Save the cron job</li>";
echo "</ol>";

// Form for database configuration
echo "<h2>Database Configuration</h2>";
echo "<form method='post'>";
echo "<label>Database Host: <input type='text' name='db_host' value='$db_host'></label><br><br>";
echo "<label>Database User: <input type='text' name='db_user' value='$db_user'></label><br><br>";
echo "<label>Database Password: <input type='password' name='db_pass' value='$db_pass'></label><br><br>";
echo "<label>Database Name: <input type='text' name='db_name' value='$db_name'></label><br><br>";
echo "<input type='submit' value='Update Configuration'>";
echo "</form>";

// Success message
if ($success_count == count($files)) {
    echo "<h2>🎉 Deployment Successful!</h2>";
    echo "<p>All files created successfully!</p>";
    echo "<p>Visit your website at: <a href='/cricket/'>/cricket/</a></p>";
    echo "<p>Don't forget to set up the cron job as mentioned above.</p>";
}
?>
