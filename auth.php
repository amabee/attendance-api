<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
include('connection.php');

class Auth
{
    private $conn;

    public function __construct()
    {
        $this->conn = DatabaseConnection::getInstance()->getConnection();
    }

    public function signup($json)
    {
        $data = json_decode($json, true);

        if (!isset($data['name'], $data['student_number'], $data['contact_information'], $data['year_level'])) {
            return json_encode(["error" => "Missing required fields"]);
        }

        $name = $data['name'];
        $student_number = $data['student_number'];
        $contact_information = $data['contact_information'];
        $year_level = $data['year_level'];

        $check_sql = "SELECT student_id FROM students WHERE student_number = :student_number";
        $check_stmt = $this->conn->prepare($check_sql);
        $check_stmt->bindParam(':student_number', $student_number);
        $check_stmt->execute();

        if ($check_stmt->rowCount() > 0) {
            return json_encode(["error" => "Student already exists"]);
        }

        $sql = "INSERT INTO students (name, student_number, contact_information, year_level, created_at) 
                VALUES (:name, :student_number, :contact_information, :year_level, NOW())";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':student_number', $student_number);
        $stmt->bindParam(':contact_information', $contact_information);
        $stmt->bindParam(':year_level', $year_level);

        if ($stmt->execute()) {
            return json_encode(["success" => "Signup successful"]);
        } else {
            return json_encode(["error" => "Signup failed: " . $stmt->errorInfo()[2]]);
        }
    }

    public function login($json)
    {
        $data = json_decode($json, true);

        if (!isset($data['student_number']) || !isset($data['contact_information'])) {
            return json_encode(["error" => "Missing required fields"]);
        }

        $student_number = $data['student_number'];
        $contact_information = $data['contact_information'];

        $sql = "SELECT students.*, tribus.tribu_name FROM `students` 
                LEFT JOIN tribus ON students.tribu_id = tribus.tribu_id WHERE `student_number` = :student_number AND contact_information = :cinfo";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':student_number', $student_number);
        $stmt->bindParam(':cinfo', $contact_information);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return json_encode(array("success" => $result));
        } else {
            return json_encode(["error" => "Student not found"]);
        }
    }

    public function adminSignup($json)
    {
        $data = json_decode($json, true);

        if (!isset($data['fullname'], $data['email'], $data['password'])) {
            return json_encode(["error" => "Missing required fields"]);
        }

        $fullname = $data['fullname'];
        $email = $data['email'];
        $password = sha1($data['password']);

        $check_sql = "SELECT id FROM admin WHERE email = :email";
        $check_stmt = $this->conn->prepare($check_sql);
        $check_stmt->bindParam(':email', $email);
        $check_stmt->execute();

        if ($check_stmt->rowCount() > 0) {
            return json_encode(["error" => "Admin already exists"]);
        }

        $sql = "INSERT INTO admin (fullname, email, password) 
                VALUES (:fullname, :email, :password)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':fullname', $fullname);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $password);

        if ($stmt->execute()) {
            return json_encode(["success" => "Admin registration Success!"]);
        } else {
            return json_encode(["error" => "Signup failed: " . $stmt->errorInfo()[2]]);
        }
    }

    public function adminLogin($json)
    {
        $data = json_decode($json, true);

        if (!isset($data['email']) || !isset($data['password'])) {
            return json_encode(["error" => "Missing required fields"]);
        }

        $email = $data['email'];
        $password = sha1($data['password']);

        $sql = "SELECT id, fullname FROM admin WHERE email = :email AND password = :password";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $password);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return json_encode(array("success" => $result));
        } else {
            return json_encode(["error" => "Invalid email or password"]);
        }
    }
}

$auth = new Auth();

if ($_SERVER["REQUEST_METHOD"] == "GET" || $_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_REQUEST['operation']) && isset($_REQUEST['json'])) {
        $operation = $_REQUEST['operation'];
        $json = $_REQUEST['json'];

        switch ($operation) {
            case 'signup':
                echo $auth->signup($json);
                break;
            case 'login':
                echo $auth->login($json);
                break;
            case 'adminSignup':
                echo $auth->adminSignup($json);
                break;
            case 'adminLogin':
                echo $auth->adminLogin($json);
                break;
            default:
                echo json_encode(["error" => "Invalid operation"]);
                break;
        }
    } else {
        echo json_encode(["error" => "Missing parameters"]);
    }
} else {
    echo json_encode(["error" => "Invalid request method"]);
}
?>