<!DOCTYPE html>
<html>
<head>
    <title>JavaScript Test</title>
</head>
<body>
    <button onclick="testFunction()">Test Button</button>
    <button onclick="copyToClipboard('test')">Copy Test</button>
    
    <script>
        function testFunction() {
            alert('Test function works!');
        }
        
        function copyToClipboard(text) {
            alert('Copy function called with: ' + text);
        }
    </script>
</body>
</html>