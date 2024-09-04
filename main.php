<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
include('connection.php');

class Main
{
    private $conn;

    public function __construct()
    {
        $this->conn = DatabaseConnection::getInstance()->getConnection();
    }

    // CRUD for Students
    public function createStudent($json)
    {
        $data = json_decode($json, true);
        $sql = "INSERT INTO students (name, student_number, contact_information, year_level, created_at) 
                VALUES (:name, :student_number, :contact_information, :year_level, NOW())";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':student_number', $data['student_number']);
        $stmt->bindParam(':contact_information', $data['contact_information']);
        $stmt->bindParam(':year_level', $data['year_level']);
        if ($stmt->execute()) {
            return json_encode(["success" => "Student created successfully"]);
        } else {
            return json_encode(["error" => "Failed to create student: " . $stmt->errorInfo()[2]]);
        }
    }

    public function getStudents()
    {
        $sql = "SELECT * FROM students";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return json_encode(["success" => $result]);
        }

    }

    public function updateStudent($json)
    {
        $data = json_decode($json, true);
        $sql = "UPDATE students SET name = :name, student_number = :student_number, 
                contact_information = :contact_information, year_level = :year_level 
                WHERE student_id = :student_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':student_number', $data['student_number']);
        $stmt->bindParam(':contact_information', $data['contact_information']);
        $stmt->bindParam(':year_level', $data['year_level']);
        $stmt->bindParam(':student_id', $data['student_id']);
        if ($stmt->execute()) {
            return json_encode(["success" => true, "message" => "Student updated successfully"]);
        } else {
            return json_encode(["error" => "Failed to update student: " . $stmt->errorInfo()[2]]);
        }
    }

    public function deleteStudent($json)
    {
        $data = json_decode($json, true);
        $sql = "DELETE FROM students WHERE student_id = :student_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':student_id', $data['student_id']);
        if ($stmt->execute()) {
            return json_encode(["success" => true, "message" => "Student deleted successfully"]);
        } else {
            return json_encode(["error" => "Failed to delete student: " . $stmt->errorInfo()[2]]);
        }
    }

    // CRUD for Tribus
    public function createTribu($json)
    {
        $data = json_decode($json, true);
        $sql = "INSERT INTO tribus (tribu_name, created_at) VALUES (:name, NOW())";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':name', $data['name']);
        if ($stmt->execute()) {
            return json_encode(["success" => true, "message" => "Tribu created successfully"]);
        } else {
            return json_encode(["error" => "Failed to create tribu: " . $stmt->errorInfo()[2]]);
        }
    }

    public function getTribus()
    {
        $sql = "SELECT * FROM tribus";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $result = json_encode(array("success" => $stmt->fetchAll(PDO::FETCH_ASSOC)));
            return $result;
        }

    }

    public function updateTribu($json)
    {
        $data = json_decode($json, true);
        $sql = "UPDATE tribus SET tribu_name = :name WHERE tribu_id = :tribu_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':tribu_id', $data['tribu_id']);
        if ($stmt->execute()) {
            return json_encode(["success" => true, "message" => "Tribu updated successfully"]);
        } else {
            return json_encode(["error" => "Failed to update tribu: " . $stmt->errorInfo()[2]]);
        }
    }

    public function deleteTribu($json)
    {
        $data = json_decode($json, true);
        $sql = "DELETE FROM tribus WHERE tribu_id = :tribu_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':tribu_id', $data['tribu_id']);
        if ($stmt->execute()) {
            return json_encode(["success" => true, "message" => "Tribu deleted successfully"]);
        } else {
            return json_encode(["error" => "Failed to delete tribu: " . $stmt->errorInfo()[2]]);
        }
    }

    // CRUD for Tribu Assignments

    public function getStudentsWithoutTribu()
    {
        $sql = "SELECT * FROM students WHERE tribu_id IS NULL";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($students) {
            return json_encode(["success" => $students]);
        } else {
            return json_encode(["error" => "No students found without a tribu"]);
        }
    }

    public function assignStudentToTribu($json)
    {
        $data = json_decode($json, true);
        $tribu_id = $data['tribu_id'];
        $student_ids = $data['student_ids'];

        $placeholders = implode(',', array_fill(0, count($student_ids), '?'));
        $sql = "UPDATE students SET tribu_id = ? WHERE student_id IN ($placeholders)";
        $stmt = $this->conn->prepare($sql);

        // Bind parameters
        $params = array_merge([$tribu_id], $student_ids);
        if ($stmt->execute($params)) {
            return json_encode(["success" => true, "message" => "Students assigned to tribu successfully"]);
        } else {
            return json_encode(["error" => "Failed to assign students to tribu: " . $stmt->errorInfo()[2]]);
        }
    }


    // Attendance Monitoring
    public function checkInStudent($json)
    {
        $data = json_decode($json, true);
        $student_id = $data['student_id'];

        // Check if student is already checked in
        $checkSql = "SELECT * FROM attendance WHERE student_id = :student_id AND check_out_time IS NULL";
        $checkStmt = $this->conn->prepare($checkSql);
        $checkStmt->bindParam(':student_id', $student_id);
        $checkStmt->execute();

        if ($checkStmt->rowCount() > 0) {
            return json_encode(["error" => "Student is already checked in"]);
        }

        // Record new check-in
        $sql = "INSERT INTO attendance (student_id, check_in_time) VALUES (:student_id, NOW())";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':student_id', $student_id);
        if ($stmt->execute()) {
            return json_encode(["success" => true, "message" => "Check-in recorded successfully"]);
        } else {
            return json_encode(["error" => "Failed to record check-in: " . $stmt->errorInfo()[2]]);
        }
    }

    public function checkOutStudent($json)
    {
        $data = json_decode($json, true);
        $student_id = $data['student_id'];

        // Check if student has checked in and not yet checked out
        $checkSql = "SELECT * FROM attendance WHERE student_id = :student_id AND check_out_time IS NULL";
        $checkStmt = $this->conn->prepare($checkSql);
        $checkStmt->bindParam(':student_id', $student_id);
        $checkStmt->execute();

        if ($checkStmt->rowCount() === 0) {
            return json_encode(["error" => "No check-in record found for this student or already checked out"]);
        }

        // Record check-out
        $sql = "UPDATE attendance SET check_out_time = NOW() WHERE student_id = :student_id AND check_out_time IS NULL";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':student_id', $student_id);
        if ($stmt->execute()) {
            return json_encode(["success" => true, "message" => "Check-out recorded successfully"]);
        } else {
            return json_encode(["error" => "Failed to record check-out: " . $stmt->errorInfo()[2]]);
        }
    }

    // Attendance Reports
    public function getAttendanceByStudent($json)
    {
        $data = json_decode($json, true);
        $sql = "SELECT * FROM attendance WHERE student_id = :student_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':student_id', $data['student_id']);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return json_encode($result);
    }

    public function getAttendanceByTribu($json)
    {
        $data = json_decode($json, true);
        $sql = "SELECT t.tribu_name,
                       COUNT(a.student_id) AS total_count,
                       SUM(CASE WHEN a.check_out_time IS NOT NULL THEN 1 ELSE 0 END) AS present_count,
                       COUNT(a.student_id) - SUM(CASE WHEN a.check_out_time IS NOT NULL THEN 1 ELSE 0 END) AS absent_count
                FROM attendance a
                JOIN students s ON a.student_id = s.student_id
                JOIN tribus t ON s.tribu_id = t.tribu_id
                WHERE s.tribu_id = :tribu_id
                GROUP BY t.tribu_name";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':tribu_id', $data['tribu_id']);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return json_encode(array("success" => $result));
    }
    public function getAttendanceByYearLevel($json)
    {
        $data = json_decode($json, true);
        $sql = "SELECT s.year_level, COUNT(a.student_id) as count 
                FROM attendance a 
                JOIN students s ON a.student_id = s.student_id 
                WHERE s.year_level = :year_level
                GROUP BY s.year_level";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':year_level', $data['year_level']);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return json_encode($result);
    }

    public function getCombinedTribuYearLevelReport($json)
    {
        $data = json_decode($json, true);
        $sql = "SELECT s.tribu_id, s.year_level, COUNT(a.student_id) as count 
                FROM attendance a 
                JOIN students s ON a.student_id = s.student_id 
                WHERE s.tribu_id = :tribu_id AND s.year_level = :year_level
                GROUP BY s.tribu_id, s.year_level";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':tribu_id', $data['tribu_id']);
        $stmt->bindParam(':year_level', $data['year_level']);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return json_encode($result);
    }

    public function getAttendanceByEvent($json)
    {
        $data = json_decode($json, true);
        $sql = "SELECT s.student_id, s.name, s.student_number, t.tribu_name, a.check_in_time, a.check_out_time
                FROM attendance a
                JOIN students s ON a.student_id = s.student_id
                JOIN tribus t ON s.tribu_id = t.tribu_id
                WHERE a.event_id = :event_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':event_id', $data['event_id']);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return json_encode(array("success" => $result));
    }


    // CRUD for Events
    public function createEvent($json)
    {
        $data = json_decode($json, true);
        $sql = "INSERT INTO events (event_name, event_start_time, event_end_time, isActive, created_at) 
                 VALUES (:event_name, :event_start_time, :event_end_time, 1, NOW())";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':event_name', $data['event_name']);
        $stmt->bindParam(':event_start_time', $data['event_start_time']);
        $stmt->bindParam(':event_end_time', $data['event_end_time']);
        if ($stmt->execute()) {
            return json_encode(["success" => true, "message" => "Event created successfully"]);
        } else {
            return json_encode(["error" => "Failed to create event: " . $stmt->errorInfo()[2]]);
        }
    }

    public function getEvents()
    {
        $sql = "SELECT * FROM events";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return json_encode(["success" => $result]);
        } else {
            return json_encode(["error" => "No events found"]);
        }
    }

    public function updateEvent($json)
    {
        $data = json_decode($json, true);
        $sql = "UPDATE events SET event_name = :event_name, event_start_time = :event_start_time, 
                 event_end_time = :event_end_time, isActive = :isActive WHERE event_id = :event_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':event_name', $data['event_name']);
        $stmt->bindParam(':event_start_time', $data['event_start_time']);
        $stmt->bindParam(':event_end_time', $data['event_end_time']);
        $stmt->bindParam(':isActive', $data['isActive']); // Bind isActive parameter
        $stmt->bindParam(':event_id', $data['event_id']);
        if ($stmt->execute()) {
            return json_encode(["success" => true, "message" => "Event updated successfully"]);
        } else {
            return json_encode(["error" => "Failed to update event: " . $stmt->errorInfo()[2]]);
        }
    }

    public function deleteEvent($json)
    {
        $data = json_decode($json, true);
        $sql = "UPDATE events SET isActive = 0 WHERE event_id = :event_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':event_id', $data['event_id']);
        if ($stmt->execute()) {
            return json_encode(["success" => "Event deactivated successfully"]);
        } else {
            return json_encode(["error" => "Failed to deactivate event: " . $stmt->errorInfo()[2]]);
        }
    }

}

$main = new Main();

if ($_SERVER["REQUEST_METHOD"] == "GET" || $_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_REQUEST['operation']) && isset($_REQUEST['json'])) {
        $operation = $_REQUEST['operation'];
        $json = $_REQUEST['json'];

        switch ($operation) {
            // Student Operations
            case 'createStudent':
                echo $main->createStudent($json);
                break;
            case 'getStudents':
                echo $main->getStudents();
                break;
            case 'updateStudent':
                echo $main->updateStudent($json);
                break;
            case 'deleteStudent':
                echo $main->deleteStudent($json);
                break;

            // Tribu Operations
            case 'createTribu':
                echo $main->createTribu($json);
                break;
            case 'getTribus':
                echo $main->getTribus();
                break;
            case 'updateTribu':
                echo $main->updateTribu($json);
                break;
            case 'deleteTribu':
                echo $main->deleteTribu($json);
                break;

            // Tribu Assignment
            case 'assignStudentToTribu':
                echo $main->assignStudentToTribu($json);
                break;
            case 'getStudentsWithoutTribu':
                echo $main->getStudentsWithoutTribu();
                break;

            // Attendance Monitoring
            case 'checkInStudent':
                echo $main->checkInStudent($json);
                break;
            case 'checkOutStudent':
                echo $main->checkOutStudent($json);
                break;

            // Attendance Reports
            case 'getAttendanceByStudent':
                echo $main->getAttendanceByStudent($json);
                break;
            case 'getAttendanceByTribu':
                echo $main->getAttendanceByTribu($json);
                break;
            case 'getAttendanceByYearLevel':
                echo $main->getAttendanceByYearLevel($json);
                break;
            case 'getCombinedTribuYearLevelReport':
                echo $main->getCombinedTribuYearLevelReport($json);
                break;
            case 'getAttendanceByEvent':
                echo $main->getAttendanceByEvent($json);
                break;

            // Event Operations
            case 'createEvent':
                echo $main->createEvent($json);
                break;
            case 'getEvents':
                echo $main->getEvents();
                break;
            case 'updateEvent':
                echo $main->updateEvent($json);
                break;
            case 'deleteEvent':
                echo $main->deleteEvent($json);
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