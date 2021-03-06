<?php
include_once '../object/Scan.php';
include_once '../object/Room.php';
include_once '../object/User.php';
include_once '../object/Building.php';
include_once '../config/Database.php';

/**
 * Klasa odpowiadajaca za obsluge skanowania
 */
class ScanRepository
{
    private $conn;

    /**
     * ScanRepository constructor.
     * @param PDO $db polaczenie z bazą
     */
    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * Funkcja znajduje i zwraca wszystkie dotychczasowe skany danego uzytkownika po jego id
     * @param integer $user_id  id uzytkownika dla ktorego funkcja wyszukuje i zwraca skany
     * @return array|mixed  tablica skanow
     */
    public function getScans($user_id)
    {
        $query = "CALL getScans(?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1,$user_id);

        $stmt->execute();

        $scans_array = array();
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if($row['message']!=null)
            {
                return $row['message'];
            }
            $scans_array [] = self::createScan($row);
        }
        return $scans_array;
    }

    /**
     * Funkcja tworzy i zwraca skan na podstawie przekazanego wyniku kwerendy
     * @param array $row wynik kwerendy fetch
     * @return Scan utworzony skan
     */
    private static function createScan($row)
    {
        $scan = new Scan();
        $scan->setId($row['id']);

        $building = new Building();
        $building->setId($row['building_id']);
        $building->setName($row['building_name']);

        $room = new Room();
        $room->setId($row['room_id']);
        $room->setName($row['room_name']);
        $room->setBuilding($building);

        $scan->setRoom($room);

        $owner = new User();
        $owner->setId($row['owner_id']);
        $owner->setLogin($row['owner_name']);

        $scan->setOwner($owner);
        $scan->setCreateDate($row['create_date']);
        return $scan;
    }

    /**
     * Funkcja dodaje nowy skan do tabeli
     * @param integer $room_id id pokoju ktorego skan dotyczy
     * @param integer $user_id id uzytkownika ktorego skan dotyczy
     * @return array|null[] dodany skan
     */
    public function addScan($room_id, $user_id)
    {
        $query = "CALL addScan(:room_id,:owner)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':room_id', $room_id);
        $stmt->bindParam(':owner', $user_id);

        if($stmt->execute())
        {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return [
                'message' => $row['message'],
                'id' => $row['id']
            ];
        }
        return ["message" => null, "id" => null];
    }

    /**
     * Funkcja usuwa skan o danym id
     * @param integer $id id usuwanego skanu
     * @return bool czy udalo sie usunac skan
     */
    public function deleteScan($id)
    {
        $query = "CALL deleteScan(?)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(1,$id);
        return $stmt->execute();
    }

    /**
     * Funkcja aktualizujaca dany skan po jego id
     * @param integer $scan_id id aktualizowanego skanu
     * @param array $positions tablica pozycji (srodkow trwalych) ktore chcemy w danym skanie zaktualizowac
     * @return bool czy udalo sie zaktualizowac skan
     */
    public function updateScan($scan_id, $positions)
    {
        $query = "CALL updateScan(:id,:positions)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $scan_id);
        $positions = json_encode($positions);
        $stmt->bindParam(':positions', $positions);

        return $stmt->execute();
    }
}