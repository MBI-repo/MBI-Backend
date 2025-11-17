<!DOCTYPE html>
<html>
<head>
  <title>Conversation Test</title>
  <script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>
</head>
<body>
  <h1>Conversation Test</h1>
  <script>
    // Replace with each user's Bearer token and a real conversation id
    const TOKEN = 'f1nStr1pB8s5dWfSXj5trFfQE0OBqHcYtpyTc5Cg3cdc8937';
    const CONVERSATION_ID = 1; 

    Pusher.logToConsole = true;

    const pusher = new Pusher('3127abdf5ca7dbbb3c66', {
      cluster: 'eu',
      // Private channel auth with Sanctum
      authEndpoint: '/broadcasting/auth',
      auth: {
        headers: {
          Authorization: 'Bearer ' + TOKEN
        }
      }
    });

    // Private channel name for Laravel PrivateChannel('conversation.{id}')
    const channelName = 'private-conversation.' + CONVERSATION_ID;
    const channel = pusher.subscribe(channelName);

    // IMPORTANT: default Laravel broadcast event names are the class names
    channel.bind('App\\Events\\MessageSent', (data) => {
      console.log('MessageSent:', data);
    });
    channel.bind('App\\Events\\MessageDeleted', (data) => {
      console.log('MessageDeleted:', data);
    });
    channel.bind('App\\Events\\MessageRead', (data) => {
      console.log('MessageRead:', data);
    });

    // Optionally log everything
    channel.bind_global((event, data) => {
      console.log('Global:', event, data);
    });
  </script>
</body>
</html>