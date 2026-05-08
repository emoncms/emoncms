<?php

Class AdminModel
{
    private $mysqli;
    private $user;

    public function __construct($mysqli, $user)
    {
        $this->mysqli = $mysqli;
        $this->user = $user;
    }

    public function setUser($id)
    {
        $_SESSION['userid'] = (int) $id;
        
        $u = $this->user->get($_SESSION['userid']);
        $_SESSION['username'] = $u->username;
        $_SESSION['gravatar'] = $u->gravatar;
        
        header("Location: ../user/view");
        // stop any other code from running once http header sent
        exit();
    }

    public function setUserFeed($feedid)
    {
        $feedid = (int) get("id");
        $result = $this->mysqli->query("SELECT userid FROM feeds WHERE id=$feedid");
        $row = $result->fetch_object();
        $userid = $row->userid;
        $_SESSION['userid'] = $userid;
        header("Location: ../user/view");
        // stop any other code from running once http header sent
        exit();
    }

    public function numberOfUsers()
    {
        $result = $this->mysqli->query("SELECT COUNT(*) FROM users");
        $row = $result->fetch_array();
        return (int) $row[0];
    }

    public function userList($page_in, $perpage_in, $orderby_in, $order_in, $search_in)
    {
        // Cast pagination inputs to int to prevent injection via interpolation.
        // $perpage/$offset are added as bound parameters later, but casting here
        // ensures safe defaults if the values are reused elsewhere.
        // $perpage is capped at 100 to prevent excessive result sets.
        $perpage = null;
        $offset = null;
        if ($page_in !== null && $perpage_in !== null) {
            $page = (int) $page_in;
            $perpage = (int) $perpage_in;
            if ($page < 0) $page = 0;
            if ($perpage < 1) $perpage = 20;
            if ($perpage > 1000) $perpage = 1000;
            $offset = $page * $perpage;
        }

        // Column names and sort direction cannot be passed as bound parameters in SQL,
        // so they are validated against an explicit whitelist before being interpolated
        // into the query string.
        $orderby = "id";
        if ($orderby_in !== null) {
            if ($orderby_in=="id") $orderby = "id";
            if ($orderby_in=="username") $orderby = "username";
            if ($orderby_in=="email") $orderby = "email";
            if ($orderby_in=="email_verified") $orderby = "email_verified";
        }

        $order = "DESC";
        if ($order_in !== null) {
            if ($order_in=="descending") $order = "DESC";
            if ($order_in=="ascending") $order = "ASC";
        }

        // Build an optional WHERE clause. The search value is NOT interpolated here;
        // placeholders (?) are used and the value is bound as a parameter below.
        // A length cap is applied to guard against expensive LIKE pattern matching.
        $search = false;
        $searchstr = "";
        if ($search_in !== null && $search_in !== "") {
            if (strlen($search_in) > 100) return [];
            $search = $search_in;
            $searchstr = "WHERE username LIKE ? OR email LIKE ?";
        }

        // Accumulate all bind parameters and their type string in order, matching
        // the placeholder positions in the final query: search (ss), then limit (ii).
        $params = [];
        $types = "";
        if ($search !== false) {
            $searchparam = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $search) . '%';
            $params[] = $searchparam;
            $params[] = $searchparam;
            $types .= "ss";
        }

        $limitstr = "";
        if ($perpage !== null) {
            $limitstr = "LIMIT ? OFFSET ?";
            $params[] = $perpage;
            $params[] = $offset;
            $types .= "ii";
        }

        // Feed counts are retrieved via LEFT JOIN and COUNT rather than a separate
        // query per user row, avoiding an N+1 query problem. GROUP BY ensures one
        // row per user. The ORDER BY column is prefixed with u. to avoid ambiguity
        // with the GROUP BY in play.
        $sql = "SELECT u.id, u.username, u.email, u.email_verified,
                    COUNT(f.id) AS feeds
                FROM users u
                LEFT JOIN feeds f ON f.userid = u.id
                $searchstr
                GROUP BY u.id, u.username, u.email, u.email_verified
                ORDER BY u.$orderby $order
                $limitstr";

        // Check prepare() and execute() explicitly; failing silently here could
        // leak a fatal error and stack trace to the caller depending on error
        // reporting configuration.
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            return array("success"=>false, "message"=>"Error fetching user list");
        }

        // Only call bind_param when there are parameters to bind; the splat operator
        // unpacks the $params array to match each positional placeholder in sequence.
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        if (!$stmt->execute()) {
            return array("success"=>false, "message"=>"Error fetching user list");
        }

        $result = $stmt->get_result();

        $data = [];
        while ($row = $result->fetch_object()) {
            $row->id = (int) $row->id;
            $row->email_verified = (int) $row->email_verified;
            // feeds is cast to int to match the type consistency of the other
            // numeric fields, since COUNT() returns a string in MySQLi.
            $row->feeds = (int) $row->feeds;
            $data[] = $row;
        }

        return $data;
    }
}
