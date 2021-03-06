<?php

require_once __DIR__ . '/../auth/mysql_config.php';
require_once __DIR__ . '/../auth/smtp_config.php';
require_once __DIR__ . '/../auth/login.php';

class MailBox {

    private $id;
    private $fromUserId;
    private $toUserId;
    private $title;
    private $message;
    private $sendTime;
    private $readTime;

    private function __construct($id, $fromUserId, $toUserId, $title, $message, $sendTime, $readTime) {
        $this->id = $id;
        $this->fromUserId = $fromUserId;
        $this->toUserId = $toUserId;
        $this->title = $title;
        $this->message = $message;
        $this->sendTime = $sendTime;
        $this->readTime = $readTime;
    }

    public function GetId() {
        return $this->id;
    }

    public function GetFromUserId() {
        return $this->fromUserId;
    }

    public function GetToUserId() {
        return $this->toUserId;
    }

    public function GetTitle() {
        return $this->title;
    }

    public function GetMessage() {
        return $this->message;
    }

    public function GetSendTime() {
        return $this->sendTime;
    }

    public function GetReadTime() {
        return $this->readTime;
    }

    public static function GetInboxCountForUser($userId) {
        $connection = MysqlConfig::Connect();
        $sql = "SELECT COUNT(id) FROM Mail WHERE toUserId = :userid";
        $statement = $connection->prepare($sql);
        $statement->bindValue("userid", $userId);
        $statement->execute();
        return $statement->fetchColumn();
    }

    public static function GetUnreadInboxCountForUser($userId) {
        $connection = MysqlConfig::Connect();
        $sql = "SELECT COUNT(id) FROM Mail WHERE toUserId = :userid AND readTime IS NULL";
        $statement = $connection->prepare($sql);
        $statement->bindValue("userid", $userId);
        $statement->execute();
        return $statement->fetchColumn();
    }

    public static function GetSentCountForUser($userId) {
        $connection = MysqlConfig::Connect();
        $sql = "SELECT COUNT(id) FROM Mail WHERE fromUserId = :userid";
        $statement = $connection->prepare($sql);
        $statement->bindValue("userid", $userId);
        $statement->execute();
        return $statement->fetchColumn();
    }

    public static function GetReadCountForUser($userId) {
        $connection = MysqlConfig::Connect();
        $sql = "SELECT COUNT(id) FROM Mail WHERE toUserId = :userid AND readTime IS NOT NULL";
        $statement = $connection->prepare($sql);
        $statement->bindValue("userid", $userId);
        $statement->execute();
        return $statement->fetchColumn();
    }

    public static function GetUnreadMailForUser($userId) {
        $connection = MysqlConfig::Connect();
        $sql = "SELECT * FROM Mail WHERE toUserId = :userid AND readTime IS NULL";
        $statement = $connection->prepare($sql);
        $statement->bindValue("userid", $userId);
        $statement->execute();

        return MailBox::GetMailFromSqlResult($statement);
    }

    public static function GetReadMailForUser($userId) {
        $connection = MysqlConfig::Connect();
        $sql = "SELECT * FROM Mail WHERE toUserId = :userid AND readTime IS NOT NULL";
        $statement = $connection->prepare($sql);
        $statement->bindValue("userid", $userId);
        $statement->execute();

        return MailBox::GetMailFromSqlResult($statement);
    }

    public static function GetSentMailForUser($userId) {
        $connection = MysqlConfig::Connect();
        $sql = "SELECT * FROM Mail WHERE fromUserId = :userid";
        $statement = $connection->prepare($sql);
        $statement->bindValue("userid", $userId);
        $statement->execute();

        return MailBox::GetMailFromSqlResult($statement);
    }

    public static function SetAllReadForUser($userId) {
        $readTime = new DateTime();
        $readTime->format('Y-m-d H:i:s');

        $connection = MysqlConfig::Connect();
        $sql = "UPDATE Mail SET readTime = :readtime WHERE toUserId = :userid AND readTime IS NULL";
        $statement = $connection->prepare($sql);
        $statement->bindValue("userid", $userId);
        $statement->bindValue("readtime", $readTime->format('Y-m-d H:i:s'));
        $statement->execute();
    }

    public static function InsertNewMail($fromUserId, $toUserId, $title, $message) {
        $connection = MysqlConfig::Connect();
        $sql = "INSERT INTO Mail (fromUserId, toUserId, title, message) VALUES (:from, :to, :title, :message)";
        $statement = $connection->prepare($sql);
        $statement->bindValue("from", $fromUserId);
        $statement->bindValue("to", $toUserId);
        $statement->bindValue("title", $title);
        $statement->bindValue("message", $message);
        $statement->execute();
    }

    private static function GetMailFromSqlResult($statement) {
        $mail = array();
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $id = $row['id'];
            $from = $row['fromUserId'];
            $to = $row['toUserId'];
            $title = $row['title'];
            $message = $row['message'];
            $sendTime = $row['sendTime'];
            $readTime = $row['readTime'];

            $newMail = new MailBox($id, $from, $to, $title, $message, $sendTime, $readTime);
            array_push($mail, $newMail);
        }
        return $mail;
    }

}

$toUser = filter_input(INPUT_POST, "toUser", FILTER_SANITIZE_STRING);
$title = filter_input(INPUT_POST, "title", FILTER_SANITIZE_STRING);
$message = filter_input(INPUT_POST, "message", FILTER_SANITIZE_STRING);

if (!$toUser || !$title || !$message) {
    return;
}

require_once __DIR__.'/../header/auth_header.php';

$fromUser = $_SESSION['userid'];
$toUser = User::GetIdFromUsername($toUser);

MailBox::InsertNewMail($fromUser, $toUser, $title, $message);

$email = User::GetEmailFromId($toUser);
$subject = "OpenVLE - New Mail";

$body = "<p>You have new mail in your OpenVLE Inbox!</p>";
$body .= "<p><strong>From: </strong> " . User::GetUsernameFromId($fromUser) . "</br>";
$body .= "<p><strong>Title: </strong> " . $title . "</br></p>";
$body .= "<strong>Message</strong></br><p>" . $message . "</p>";

SmtpConfig::SendMail($email, $subject, $body);