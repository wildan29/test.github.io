<?php
    use Kreait\Firebase\Factory;
    use Kreait\Firebase\Messaging\CloudMessage;
    use Kreait\Firebase\Messaging\Notification;

    class MyNotification {

        private $database = null;
        private $userToken = null;
        
        function _construct() {
            
        }

        function send($msg) {

            $factory = (new Factory)->withServiceAccount(__DIR__ . '\firebase_\zakatsukses-skripsi-firebase-adminsdk-5amem-b4ce1de070.json');

            $messaging = $factory->createMessaging();

            $message = CloudMessage::withTarget('token', $this->userToken)
                ->withNotification(Notification::fromArray([
                    'title' => "Zakat Sukses",
                    'body' => $msg
                ]));

            $messaging->send($message);
        }

        function getTokenFromUserId($userId) {
            $stmt = $this->database->prepare("SELECT token FROM fcm_tokens WHERE user_id = :uid");
            $stmt->bindParam(':uid', $userId);
            
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $this->userToken = $result['token'];
        }

        function initDb($db) {
            $this->database = $db;
        }
    }