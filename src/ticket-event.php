<?php
class Event{
    
    
    public $id = '';

    public $ticket = '';

    public $user = '';

    public $body = '';

    private $db = null;


    public function __construct($data = null) 
    {
        $this->ticket = isset($data['ticket']) ? $data['ticket'] : null ;
        $this->user = isset($data['user']) ? $data['user'] : null ;
        $this->body = isset($data['body']) ? $data['body'] : null ;

        $this->db = Database::getInstance();

        return $this;
    }

    public function save() : Event 
    {
        $stmt = $this->db->prepare(
            "INSERT INTO ticket_event (ticket, user, body)
             VALUES (?, ?, ?)"
        );

        if ($stmt === false) {
            throw new Exception($this->db->error);
        }

        $ticketId = (int)$this->ticket;
        $userId = (int)$this->user;
        $stmt->bind_param('iis', $ticketId, $userId, $this->body);

        if (!$stmt->execute()) {
            $error = $stmt->error ?: $this->db->error;
            $stmt->close();
            throw new Exception($error);
        }

        $this->id = $this->db->insert_id;
        $this->ticket = $ticketId;
        $this->user = $userId;
        $this->created_at = date('Y-m-d H:i:s');
        $stmt->close();

        return $this;
    }

    public static function find($id) : Event
    {
        $sql ="SELECT * FROM ticket_event WHERE ticket = '$id'";
        $self = new static;
        $res = $self->db->query($sql);
        if($res->num_rows < 1) return $self;
        $self->populateObject($res->fetch_object());
        return $self;
    }

    public static function findAll() : array 
    {
        $sql = "SELECT * FROM ticket_event ORDER BY id DESC";
        $tickets = [];
        $self = new static;
        $res = $self->db->query($sql);
        
        if($res->num_rows < 1) return new static;

        while($row = $res->fetch_object()){
            $ticket = new static;
            $ticket->populateObject($row);
            $tickets[] = $ticket;
        }

        return $tickets;
    }

    public static function findByTicket($id) : array 
    {
        $sql = "SELECT * FROM ticket_event WHERE ticket = '$id'";
        //print_r($sql);die();
        $events = [];
        $self = new static;
        $res = $self->db->query($sql);
        
        if($res->num_rows < 1) return [];

        while($row = $res->fetch_object()){
            $event = new static;
            $event->populateObject($row);
            $events[] = $event;
        }

        return $events;
    }

    public function populateObject($object) : void{

        foreach($object as $key => $property){
            $this->$key = $property;
        }
    }


}
