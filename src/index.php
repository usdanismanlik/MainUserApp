<?php

header('Content-Type: application/json');

// Database connection parameters
$host = 'db';
$db = 'user_auth_db';
$user = 'root';
$pass = 'root';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Simple Router
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));

if ($pathParts[0] === 'user' && isset($pathParts[1]) && is_numeric($pathParts[1])) {
    $userId = (int) $pathParts[1];

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        getUserDetails($pdo, $userId);
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed. This is a read-only application.']);
    }
} elseif ($pathParts[0] === 'firma' && isset($pathParts[1]) && is_numeric($pathParts[1])) {
    $userId = (int) $pathParts[1];

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        getFirmaDetails($pdo, $userId);
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed. This is a read-only application.']);
    }
} elseif ($pathParts[0] === 'personel' && isset($pathParts[1]) && is_numeric($pathParts[1])) {
    $firmaId = (int) $pathParts[1];

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        getPersonelByFirma($pdo, $firmaId);
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed. This is a read-only application.']);
    }
} elseif ($pathParts[0] === 'grup' && isset($pathParts[1]) && is_numeric($pathParts[1])) {
    $groupId = (int) $pathParts[1];

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        getUsersByGroup($pdo, $groupId);
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed. This is a read-only application.']);
    }
} elseif ($pathParts[0] === 'grups' && isset($pathParts[1]) && is_numeric($pathParts[1])) {
    $firmaId = (int) $pathParts[1];

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        getGroupsByFirma($pdo, $firmaId);
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed. This is a read-only application.']);
    }
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Endpoint not found']);
}

function getUserDetails($pdo, $userId)
{
    try {
        $userData = fetchUserData($pdo, $userId);

        if ($userData) {
            echo json_encode(recursiveJsonDecode($userData));
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
        }
    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Query failed: ' . $e->getMessage()]);
    }
}

function getFirmaDetails($pdo, $userId)
{
    try {
        $mainUser = fetchUserData($pdo, $userId);

        if (!$mainUser) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            return;
        }

        $username = $mainUser['username'];

        // Fetch all users with the same username (Organization ID)
        // Excluding the main user itself from the list? The requirement says "other users", 
        // but usually a list includes everyone or excludes the caller. 
        // "aynı username'deki diğer kullanıcıları da dönecek" implies "others".
        // Let's include all for completeness or strictly others? 
        // "bu organizasyona bağlı tüm userları getirmeli" -> "all users".
        // I will include ALL users with that username, including the main user in the list, 
        // or just put them in 'organization_users'.
        // Let's follow the plan: main_user + organization_users list.

        $stmt = $pdo->prepare("SELECT id FROM main_users WHERE username = ?");
        $stmt->execute([$username]);
        $relatedUserIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $organizationUsers = [];
        foreach ($relatedUserIds as $relatedId) {
            // Optimization: fetchUserData might be heavy if called in loop. 
            // But for now it ensures consistency.
            if ($relatedId != $userId) {
                $organizationUsers[] = fetchUserData($pdo, $relatedId);
            }
        }

        echo json_encode(recursiveJsonDecode([
            'main_user' => $mainUser,
            'organization_users' => $organizationUsers
        ]));

    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Query failed: ' . $e->getMessage()]);
    }
}

function getPersonelByFirma($pdo, $firmaId)
{
    try {
        // Find users who have meta 'personelFirma' equal to $firmaId
        $stmt = $pdo->prepare("SELECT user FROM main_userMeta WHERE meta = 'personelFirma' AND value = ?");
        $stmt->execute([$firmaId]);
        $userIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $personelList = [];
        foreach ($userIds as $uid) {
            $userData = fetchUserData($pdo, $uid);
            if ($userData) {
                $personelList[] = $userData;
            }
        }

        echo json_encode(recursiveJsonDecode($personelList));

    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Query failed: ' . $e->getMessage()]);
    }
}

function getUsersByGroup($pdo, $groupId)
{
    try {
        $stmt = $pdo->prepare("SELECT id FROM main_users WHERE grup = ?");
        $stmt->execute([$groupId]);
        $userIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $userList = [];
        foreach ($userIds as $uid) {
            $userData = fetchUserData($pdo, $uid);
            if ($userData) {
                $userList[] = $userData;
            }
        }

        echo json_encode(recursiveJsonDecode($userList));

    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Query failed: ' . $e->getMessage()]);
    }
}

function getGroupsByFirma($pdo, $firmaId)
{
    try {
        $stmt = $pdo->prepare("SELECT * FROM main_subuser_grups WHERE user = ?");
        $stmt->execute([$firmaId]);
        $groups = $stmt->fetchAll();

        echo json_encode(recursiveJsonDecode($groups));

    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Query failed: ' . $e->getMessage()]);
    }
}

function fetchUserData($pdo, $userId)
{
    try {
        // Fetch Main User Info
        $stmt = $pdo->prepare("SELECT * FROM main_users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            return null;
        }

        // Fetch User Meta
        $stmtMeta = $pdo->prepare("SELECT meta, value FROM main_userMeta WHERE user = ?");
        $stmtMeta->execute([$userId]);
        $metaRaw = $stmtMeta->fetchAll();

        $meta = [];
        foreach ($metaRaw as $m) {
            $val = $m['value'];
            $lowerVal = strtolower($val);
            if (in_array($lowerVal, ['yes', 'evet', '1'])) {
                $val = true;
            } elseif (in_array($lowerVal, ['no', 'hayir', '0'])) {
                $val = false;
            }
            $meta[$m['meta']] = $val;
        }

        // Merge meta into user
        $user = array_merge($user, $meta);

        // Fetch Subuser Group Info
        // Logic: Check if 'grup' column in main_users is set, or check main_subuser_grups by user id
        // Based on table inspection: main_subuser_grups has a 'user' column.
        $group = null;

        // Try fetching by user id in subuser_grups
        $stmtGroup = $pdo->prepare("SELECT * FROM main_subuser_grups WHERE user = ?");
        $stmtGroup->execute([$userId]);
        $groupData = $stmtGroup->fetch();

        if ($groupData) {
            $group = $groupData;
        } elseif (!empty($user['grup'])) {
            // Fallback: if main_users.grup is set, maybe it links to main_subuser_grups.id
            $stmtGroupById = $pdo->prepare("SELECT * FROM main_subuser_grups WHERE id = ?");
            $stmtGroupById->execute([$user['grup']]);
            $group = $stmtGroupById->fetch();
        }

        if ($group) {
            $user['group_info'] = $group;
        }

        return $user;

    } catch (\PDOException $e) {
        // Log error or handle it? For now return null or throw
        // To keep simple, we might return null or let exception bubble up if we want 500
        throw $e;
    }
}

function recursiveJsonDecode($data)
{
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = recursiveJsonDecode($value);
        }
        return $data;
    } elseif (is_string($data)) {
        $decoded = json_decode($data, true);
        if (json_last_error() === JSON_ERROR_NONE && (is_array($decoded) || is_object($decoded))) {
            return recursiveJsonDecode($decoded); // Recurse in case the decoded JSON contains more JSON strings
        }
    }
    return $data;
}
