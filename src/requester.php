<?php
class Requester{
    
    public $id = null;
    
    public $name = '';

    public $email = '';

    public $phone = '';

    public $user_id = null;

    private $db = null;


    public function __construct($data = null) 
    {
        $this->name = isset($data['name']) ? $data['name'] :null ;
        $this->email = isset($data['email']) ? $data['email'] : null ;
        $this->phone = isset($data['phone']) ? $data['phone'] : null ;
        $this->user_id = isset($data['user_id']) ? $data['user_id'] : null ;
        
        $this->db = Database::getInstance();

        return $this;
    }

    public function save() : Requester
    {
        if ($this->hasUserIdColumn()) {
            $userId = $this->user_id !== null ? (int)$this->user_id : 'NULL';
            $sql = "INSERT INTO requester (name, email, phone, user_id)
                    VALUES ('$this->name', '$this->email', '$this->phone', $userId);
            ";
        } else {
            $sql = "INSERT INTO requester (name, email, phone)
                    VALUES ('$this->name', '$this->email', '$this->phone');
            ";
        }

        if($this->db->query($sql) === false) {
            throw new Exception($this->db->error);
        }
        $id = $this->db->insert_id;
        return self::find($id);

    }

    public static function find($id) : Requester
    {
        $sql ="SELECT * FROM requester WHERE id = '$id'";
        $self = new static;
        $res = $self->db->query($sql);
        if($res->num_rows < 1) return false;
        $self->populateObject($res->fetch_object());
        return $self;
    }

    public static function findAll() :array
    {
        $sql = "SELECT * FROM requester ORDER BY id DESC";
        $requesters = [];
        $self = new static;
        $res = $self->db->query($sql);
        
        if($res->num_rows < 1) return new static;

        while($row = $res->fetch_object()){
            $requester = new static;
            $requester->populateObject($row);
            $requesters[] = $requester;
        }

        return $requesters;
    } 

    /**
     * @param array [$column => $value] Takes an array as key value pair
     * @return  array Array of requester
     */ 
    public static function findByColumn($data) :array
    {
        $field = key($data);
        $value = $data[$field];

        $sql = "SELECT * FROM requester WHERE $field LIKE '%$value%' ORDER BY id DESC";
        $requesters = [];
        $self = new static;
        $res = $self->db->query($sql);
        
        if($res->num_rows < 1) return [];

        while($row = $res->fetch_object()){
            $requester = new static;
            $requester->populateObject($row);
            $requesters[] = $requester;
        }

        return $requesters;
    } 

    public static function delete($id) : bool 
    {
        $sql = "DELETE FROM requester WHERE id = '$id";
        $self = new static;
        return $self->db->query($sql);
    }

    /**
     * Find a requester row by email address.
     * @return Requester|false
     */
    public static function findByEmail($email)
    {
        $self = new static;
        $sql = $self->hasUserIdColumn()
            ? "SELECT id, name, email, phone, user_id FROM requester WHERE email = ? LIMIT 1"
            : "SELECT id, name, email, phone FROM requester WHERE email = ? LIMIT 1";
        $stmt = $self->db->prepare($sql);
        if ($stmt === false) {
            return false;
        }

        $stmt->bind_param('s', $email);
        if (!$stmt->execute()) {
            $stmt->close();
            return false;
        }

        if ($self->hasUserIdColumn()) {
            $stmt->bind_result($id, $name, $foundEmail, $phone, $userId);
        } else {
            $userId = null;
            $stmt->bind_result($id, $name, $foundEmail, $phone);
        }
        if (!$stmt->fetch()) {
            $stmt->close();
            return false;
        }

        $stmt->close();

        $self->id = $id;
        $self->name = $name;
        $self->email = $foundEmail;
        $self->phone = $phone;
        $self->user_id = $userId;
        return $self;
    }

    private function hasUserIdColumn() : bool
    {
        $result = $this->db->query("SHOW COLUMNS FROM requester LIKE 'user_id'");
        return $result && $result->num_rows > 0;
    }

    public function populateObject($object) : void
    {

        foreach($object as $key => $property){
            $this->$key = $property;
        }
    }    
}
