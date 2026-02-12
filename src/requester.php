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
        $userId = $this->user_id !== null ? (int)$this->user_id : 'NULL';
        $sql = "INSERT INTO requester (name, email, phone, user_id)
                VALUES ('$this->name', '$this->email', '$this->phone', $userId);
        ";
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
        $stmt = $self->db->prepare("SELECT * FROM requester WHERE email = ? LIMIT 1");
        if ($stmt === false) {
            return false;
        }

        $stmt->bind_param('s', $email);
        if (!$stmt->execute()) {
            $stmt->close();
            return false;
        }

        $res = $stmt->get_result();
        $stmt->close();

        if ($res->num_rows < 1) {
            return false;
        }

        $self->populateObject($res->fetch_object());
        return $self;
    }

    public function populateObject($object) : void
    {

        foreach($object as $key => $property){
            $this->$key = $property;
        }
    }    
}
