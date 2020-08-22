<?php
require 'config.php';

/**
 * Class User
 * класс для работы с базой данных людей
 */
class User
{

    /**
     * Ресурс базы данных
     * @var
     */
    private static $db;

    public $id;
    public $name;
    public $surname;
    public $birthdate;
    public $gender;
    public $city;


    /**
     * User constructor.
     * Конструктор класса либо создает человека в БД с заданной информацией,
     * либо берет информацию из БД по id (предусмотреть валидацию данных);
     *
     * В данной реализации парамертр $name выполняет функцию $id если передан только 1 параметр
     *
     * @param $name
     * @param null $surname
     * @param null $birthdate
     * @param null $gender
     * @param null $city
     */
    public function __construct($name, $surname = null, $birthdate = null, $gender = null, $city = null)
    {
        try {
            static::$db = new \PDO(DBMS . ':host=' . DB_HOST . ';dbname=' . DB_NAME,
                DB_USER, DB_PASS);
        } catch (PDOException $e) {
            die('Ошибка!:' . $e->getMessage() . '<br/>');
        }

        if(is_int($name)){
            $user = $this->selectUser($name);
            $this->id=$user['id'];
            $this->name=$user['name'];
            $this->surname=$user['surname'];
            $this->birthdate=$user['birthdate'];
            $this->gender=$user['gender'];
            $this->city=$user['city'];
        }
        if(!is_int($name) && !is_null($surname) && !is_null($birthdate) && !is_null($gender) && !is_null($city)){
            $this->validate($name, $surname, $birthdate, $gender, $city);
            $this->createUser($name, $surname, $birthdate, $gender, $city);
        }


    }

    /**
     * Валидация
     * @param $name
     * @param $surname
     * @param $birthdate
     * @param $gender
     * @param $city
     * @return bool
     */
    public function validate($name, $surname, $birthdate, $gender, $city)
    {
        $this->isString($name, $surname, $city);
        if (!preg_match("/^\d{4}-(0?[1-9]|1[012])-(0?[1-9]|[12][0-9]|3[01])$/", $birthdate)) {
            die("День рождения указан неверно.");
        }
        if (!preg_match("/^[0-1]{1}$/", $gender)) {
            die("Пол указан неверно.");
        }
        return true;
    }

    /**
     * Валидация на строку
     * @param mixed ...$params
     */
    public function isString(...$params)
    {
        foreach ($params as $param) {
            if (!preg_match("/^[А-Яа-яA-Za-z_-]*$/", $param)) {
                die("Имя, фамилия и город должны быть текстовыми данными.");
            }
        }
    }

    /**
     * Выбор пользователя по $id
     * @param $id
     * @return mixed
     */
    public function selectUser($id)
    {
        foreach (static::$db->query("SELECT id, name, surname, birthdate, gender, city FROM users WHERE id = $id",
                                    PDO::FETCH_ASSOC) as $row) {
            return $row;
        }
    }

    /**
     * Форматирование человека с преобразованием возраста и (или) пола (п.3 и п.4)
     * в зависимотси от параметров (возвращает новый экземпляр StdClass со всеми полями изначального класса).
     * @param $id
     * @return mixed
     */
    public function formatUser($id)
    {
        $user = $this->selectUser($id);
        $user['birthdate'] = static::getAge($id);
        $user['gender'] = static::getGender($id);
        return json_decode(json_encode($user));
    }

    /**
     * 1. Сохранение полей экземпляра класса в БД;
     * @param $name
     * @param $surname
     * @param $birthdate
     * @param $gender
     * @param $city
     */
    public function createUser($name, $surname, $birthdate, $gender, $city)
    {
        $stmt = static::$db->prepare("INSERT INTO users (name, surname, birthdate, gender, city) VALUES (?, ?, ?, ?, ?)");
        $stmt->bindParam(1, $name);
        $stmt->bindParam(2, $surname);
        $stmt->bindParam(3, $birthdate);
        $stmt->bindParam(4, $gender);
        $stmt->bindParam(5, $city);
        $stmt->execute();
    }

    /**
     * 2. Удаление человека из БД в соответствии с id объекта;
     * @param $id
     */
    public function deleteUser($id)
    {
        $stmt = static::$db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bindParam(1, $id);
        $stmt->execute();
    }

    /**
     * 3. static преобразование даты рождения в возраст (полных лет);
     * @param $userId
     * @return false|string
     */
    public static function getAge($userId)
    {
        foreach (static::$db->query("SELECT birthdate FROM users WHERE id = $userId", PDO::FETCH_ASSOC) as $row) {
            $birthdate = $row['birthdate'];
        }
        $birthdate_timestamp = strtotime($birthdate);
        $age = date('Y') - date('Y', $birthdate_timestamp);
        if (date('md', $birthdate_timestamp) > date('md')) {
            $age--;
        }
        return $age;
    }

    /**
     * 4. static преобразование пола из двоичной системы в текстовую (муж, жен);
     * @param $userId
     * @return string
     */
    public static function getGender($userId)
    {
        foreach (static::$db->query("SELECT gender FROM users WHERE id = $userId") as $row) {
            $gender = $row['gender'];
        }
        return $gender === '1' ? "мужчина" : "жещина";
    }
}
