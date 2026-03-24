<?php
class Ticket
{

    public $title = '';

    public $body = '';

    public $requester = null;

    public $team = null;

    public $team_member = null;

    public $status = '';

    public $priority = '';

    public $rating = '';


    private $db = null;

    public function __construct($data = null)
    {
        $this->title =  isset($data['title']) ? $data['title'] : null;
        $this->body = isset($data['body']) ? $data['body'] : null;
        $this->requester = isset($data['requester']) ? $data['requester'] : null;
        $this->team = isset($data['team']) ? $data['team'] : null;
        $this->team_member = isset($data['team_member']) ? $data['team_member'] : null;
        $this->status = isset($data['status']) ? $data['status'] : 'open';
        $this->priority = isset($data['priority']) ? $data['priority'] : 'low';

        $this->db = Database::getInstance();

        return $this;
    }

    public function save(): Ticket
    {
        $stmt = $this->db->prepare(
            "INSERT INTO ticket (title, body, requester, team, team_member, status, priority)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        if ($stmt === false) {
            throw new Exception($this->db->error);
        }

        $requester = $this->requester !== null ? (int)$this->requester : 0;
        $team = $this->team !== null ? (int)$this->team : null;
        $teamMember = $this->team_member !== null && $this->team_member !== '' ? (string)$this->team_member : null;
        $status = $this->status ?? 'open';
        $priority = $this->priority ?? 'low';

        $stmt->bind_param(
            'ssiisss',
            $this->title,
            $this->body,
            $requester,
            $team,
            $teamMember,
            $status,
            $priority
        );

        if (!$stmt->execute()) {
            $error = $stmt->error ?: $this->db->error;
            $stmt->close();
            throw new Exception($error);
        }

        $this->id = $this->db->insert_id;
        $this->requester = $requester;
        $this->team = $team;
        $this->team_member = $teamMember;
        $this->status = $status;
        $this->priority = $priority;
        $this->created_at = date('Y-m-d H:i:s');
        $stmt->close();

        return $this;
    }

    public static function find($id): Ticket
    {
        $sql = "SELECT * FROM ticket WHERE id = '$id'";
        $self = new static;
        $res = $self->db->query($sql);
        if ($res->num_rows < 1) {
            return false;
        }

        $self->populateObject($res->fetch_object());
        return $self;
    }

    public static function findAll(): array
    {
        $sql = "SELECT * FROM ticket ORDER BY id DESC";
        $tickets = [];
        $self = new static;
        $res = $self->db->query($sql);

        if ($res->num_rows < 1) {
            return [];
        }

        while ($row = $res->fetch_object()) {
            $ticket = new static;
            $ticket->populateObject($row);
            $tickets[] = $ticket;
        }

        return $tickets;
    }

    public static function findByStatus($status): array
    {
        $sql = "SELECT * FROM ticket WHERE status = '$status' ORDER BY id DESC";
        //print_r($sql);die();
        $self = new static;
        $tickets = [];
        $res = $self->db->query($sql);

        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_object()) {
                $ticket = new static;
                $ticket->populateObject($row);
                $tickets[] = $ticket;
            }
        }

        return $tickets;
    }

    public static function changeStatus($id, $status): bool
    {
        $self = new static;
        $sql = "UPDATE ticket SET status = '$status' WHERE id = '$id'";
        return $self->db->query($sql);
    }

    public static function delete($id): bool
    {
        $sql = "DELETE FROM ticket WHERE id = '$id'";
        // print_r($sql);die();
        $self = new static;
        return $self->db->query($sql);
    }

    public static function setRating($id, $rating): bool
    {
        $sql = "UPDATE ticket SET rating = '$rating' WHERE id = '$id'";
        $self = new static;
        return $self->db->query($sql);
    }

    public static function setPriority($id, $priority): bool
    {
        $sql = "UPDATE ticket SET priority = '$priority' WHERE id = '$id'";
        $self = new static;
        return $self->db->query($sql);
    }

    public function displayStatusBadge(): string
    {
        $badgeType = '';
        if ($this->status == 'open') {
            $badgeType = 'danger';
        } else if ($this->status == 'pending') {
            $badgeType = 'warning';
        } else if ($this->status == 'solved') {
            $badgeType = 'success';
        } else if ($this->status == 'closed') {
            $badgeType = 'info';
        }

        return '<div class="badge badge-' . $badgeType . '" role="badge"> ' . ucfirst($this->status) . '</div>';
    }

    public function populateObject($object): void
    {

        foreach ($object as $key => $property) {
            $this->$key = $property;
        }
    }

    public function update($id): Ticket
    {
        $stmt = $this->db->prepare(
            "UPDATE ticket
             SET team_member = ?, title = ?, body = ?, requester = ?, team = ?, status = ?, priority = ?
             WHERE id = ?"
        );

        if ($stmt === false) {
            throw new Exception($this->db->error);
        }

        $ticketId = (int)$id;
        $requester = $this->requester !== null ? (int)$this->requester : 0;
        $team = $this->team !== null ? (int)$this->team : null;
        $teamMember = $this->team_member !== null && $this->team_member !== '' ? (string)$this->team_member : null;
        $status = $this->status ?? 'open';
        $priority = $this->priority ?? 'low';

        $stmt->bind_param(
            'sssiissi',
            $teamMember,
            $this->title,
            $this->body,
            $requester,
            $team,
            $status,
            $priority,
            $ticketId
        );

        if (!$stmt->execute()) {
            $error = $stmt->error ?: $this->db->error;
            $stmt->close();
            throw new Exception($error);
        }

        $this->id = $ticketId;
        $this->requester = $requester;
        $this->team = $team;
        $this->team_member = $teamMember;
        $this->status = $status;
        $this->priority = $priority;
        $stmt->close();

        return $this;

    }

    public function unassigned()
    {

        $sql = "SELECT * FROM ticket WHERE team_member = '' OR team_member IS NULL ORDER BY id DESC";

        $self = new static;
        $tickets = [];
        $res = $self->db->query($sql);

        while ($row = $res->fetch_object()) {
            $tickets[] = $row;
        }

        return $tickets;

    }

    public static function findByMember($member)
     {

        $sql = "SELECT t.* FROM ticket t
                LEFT JOIN team_member tm ON tm.id = t.team_member
                WHERE t.team_member = '$member' OR tm.user = '$member'
                ORDER BY t.id DESC";
        
        $self = new static;
        $tickets = [];
        $res = $self->db->query($sql);
        
        while($row = $res->fetch_object()){
            $ticket = new static;
            $ticket->populateObject($row);
            $tickets[] = $ticket;
        }

        return $tickets;

     }

    /**
     * Tickets created by a requester email.
     */
    public static function findByRequesterEmail($email): array
    {
        $self = new static;
        $tickets = [];
        $stmt = $self->db->prepare(
            "SELECT t.id, t.title, t.body, t.requester, t.team, t.team_member, t.status, t.priority,
                    t.rating, t.created_at, t.updated_at, t.deleted_at
             FROM ticket t
             INNER JOIN requester r ON r.id = t.requester
             WHERE r.email = ?
             ORDER BY t.id DESC"
        );

        if ($stmt === false) {
            return $tickets;
        }

        $stmt->bind_param('s', $email);
        if (!$stmt->execute()) {
            $stmt->close();
            return $tickets;
        }

        $stmt->bind_result(
            $id,
            $title,
            $body,
            $requester,
            $team,
            $teamMember,
            $status,
            $priority,
            $rating,
            $createdAt,
            $updatedAt,
            $deletedAt
        );

        while ($stmt->fetch()) {
            $ticket = new static([
                'title' => $title,
                'body' => $body,
                'requester' => $requester,
                'team' => $team,
                'team_member' => $teamMember,
                'status' => $status,
                'priority' => $priority,
            ]);
            $ticket->id = $id;
            $ticket->rating = $rating;
            $ticket->created_at = $createdAt;
            $ticket->updated_at = $updatedAt;
            $ticket->deleted_at = $deletedAt;
            $tickets[] = $ticket;
        }
        $stmt->close();

        return $tickets;
    }

    /**
     * Tickets created by a logged-in requester user id.
     */
    public static function findByRequesterUserId($userId): array
    {
        $self = new static;
        $tickets = [];
        $stmt = $self->db->prepare(
            "SELECT t.id, t.title, t.body, t.requester, t.team, t.team_member, t.status, t.priority,
                    t.rating, t.created_at, t.updated_at, t.deleted_at
             FROM ticket t
             INNER JOIN requester r ON r.id = t.requester
             WHERE r.user_id = ?
             ORDER BY t.id DESC"
        );

        if ($stmt === false) {
            return $tickets;
        }

        $userId = (int)$userId;
        $stmt->bind_param('i', $userId);
        if (!$stmt->execute()) {
            $stmt->close();
            return $tickets;
        }

        $stmt->bind_result(
            $id,
            $title,
            $body,
            $requester,
            $team,
            $teamMember,
            $status,
            $priority,
            $rating,
            $createdAt,
            $updatedAt,
            $deletedAt
        );

        while ($stmt->fetch()) {
            $ticket = new static([
                'title' => $title,
                'body' => $body,
                'requester' => $requester,
                'team' => $team,
                'team_member' => $teamMember,
                'status' => $status,
                'priority' => $priority,
            ]);
            $ticket->id = $id;
            $ticket->rating = $rating;
            $ticket->created_at = $createdAt;
            $ticket->updated_at = $updatedAt;
            $ticket->deleted_at = $deletedAt;
            $tickets[] = $ticket;
        }
        $stmt->close();

        return $tickets;
    }

}
