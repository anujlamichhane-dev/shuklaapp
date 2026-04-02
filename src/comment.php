<?php

class Comment
{

    public $id = null;
    public $ticket = null;
    public $team_member = null;
    public $private = 0;
    public $body = '';
    public $created_at = null;

    private $db = null;

    public function __construct($data = null)
    {
        $data = is_array($data) ? $data : [];
        $this->ticket = isset($data['ticket-id']) ? $data['ticket-id'] : ($data['ticket'] ?? null);
        $this->team_member = isset($data['team-member']) ? $data['team-member'] : ($data['team_member'] ?? null);
        $this->private = isset($data['private']) ? (int)$data['private'] : 0;
        $this->body = isset($data['body']) ? trim((string)$data['body']) : '';

        $this->db = Database::getInstance();
        return $this;
    }

    public function save() : Comment
    {
        $ticketId = (int)$this->ticket;
        $authorId = (int)$this->team_member;
        $isPrivate = (int)$this->private;

        if ($ticketId < 1) {
            throw new Exception('Invalid ticket');
        }

        if ($authorId < 0) {
            throw new Exception('Invalid comment author');
        }

        if ($this->body === '') {
            throw new Exception('Comment body is required');
        }

        $stmt = $this->db->prepare(
            "INSERT INTO comments (ticket, team_member, private, body)
             VALUES (?, ?, ?, ?)"
        );

        if ($stmt === false) {
            throw new Exception($this->db->error);
        }

        $stmt->bind_param('iiis', $ticketId, $authorId, $isPrivate, $this->body);

        if (!$stmt->execute()) {
            $error = $stmt->error ?: $this->db->error;
            $stmt->close();
            throw new Exception($error);
        }

        $id = $this->db->insert_id;
        $stmt->close();

        return self::find($id);
    }

    public static function find($id) : Comment
    {
        $self = new static;
        $commentId = (int)$id;
        $sql = "SELECT * FROM comments WHERE id = '$commentId'";
        $res = $self->db->query($sql);
        if(!$res || $res->num_rows < 1) return $self;
        $self->populateObject($res->fetch_object());
        return $self;
    }

    public function populateObject($object) : void{

        foreach($object as $key => $property){
            $this->$key = $property;
        }
    }

    public static function findByTicket($id) : array
    {
        $ticketId = (int)$id;
        $sql = "SELECT * FROM comments WHERE ticket = '$ticketId' ORDER BY id ASC";
        $comments = [];
        $self = new static;
        $res = $self->db->query($sql);

        if(!$res || $res->num_rows < 1) return $comments;

        while($row = $res->fetch_object()){
            $comment = new static;
            $comment->populateObject($row);
            $comments[] = $comment;
        }

        return $comments;
    }
}
