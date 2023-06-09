<?php
// Set parameters for database connection
$servername = "localhost";
$username = "root";
$password = "";
$database = "test";
$data_error = array();

// Establish database connection
$conn = new mysqli($servername, $username, $password, $database);

// Check if connection is successful
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
} else {
    $message = ""; // Variable to hold success message when the form is submitted
    $pdfContent = ""; // Variable to hold the PDF content

    // Check if the form is submitted using the POST method
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
      
        // Function to check if a field is empty
        function isEmpty($field, $value) {
            global $data_error;

            if (empty($value)) {
                $data_error[$field] = "* This field cannot be left empty!";
            }

            return $value;
        }

        // Retrieve data submitted by the user
        $name = isEmpty("name", $_POST["name"]);
        $email = isEmpty("email", $_POST["email"]);
        $cms = isEmpty("cms", $_POST["cms"]);

        // If there are no empty fields, proceed with the database operations
        if (!(count($data_error))) {
            // Check if a user with the same CMS ID already exists
            $existing = "SELECT * FROM attendence WHERE cms = $cms";
            $result = $conn->query($existing);

            // If the user already exists
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $message = "Attendence of student with CMS ID " . $row["cms"] . " has already been marked!";
            }
            // If the user does not exist
            else {
                // Insert user info into the database
                $add = "INSERT INTO attendence (name, email, cms) VALUES ('$name', '$email', '$cms')";
                
                // If the insertion is successful
                if ($conn->query($add)) {
                    // Generate the PDF file
                    require_once('TCPDF/tcpdf.php');

                    // Load the invoice template
                    $invoiceTemplate = file_get_contents('template.html');

                    // Replace placeholders with submitted data
                    $invoiceTemplate = str_replace('{{name}}', $name, $invoiceTemplate);
                    $invoiceTemplate = str_replace('{{email}}', $email, $invoiceTemplate);
                    $invoiceTemplate = str_replace('{{cms}}', $cms, $invoiceTemplate);

                    $pdf = new TCPDF();
                    $pdf->AddPage();
                    $pdf->writeHTML($invoiceTemplate);

                    ob_clean(); // Clean the output buffer
                    
                    $pdfContent = $pdf->Output('', 'S'); // Get the PDF content as a string

                    $message = $name . ", your invoice has been generated!";
                }
                // If there is a technical error
                else {
                    $message = "There is some technical error. Please try again. If you are still unable to do it, please report it to your teacher. Please try again later.";
                }
            }
        }
    }

    // Check if download request is present
    if (isset($_GET['download']) && $_GET['download'] == 'true') {
        // Set the response headers to initiate the file download
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="invoice.pdf"');
        header('Content-Length: ' . strlen($pdfContent));
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');

        // Output the PDF content
        echo $pdfContent;

        exit;
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Attendance</title>
  <style>
    /* CSS styles here */
     
    form{
      width:30%;
      margin:auto;
      padding:10px;
      border:2px solid blue;
      margin-top:50px;
    }
    input{
      width:97.5%;
      height:30px;
      padding:5px;
      margin: 5px 0;
    }
    button:hover{
      cursor:pointer;
    }
    .error{
      color:red;
    }
    h4{
      text-align:center;
    }
    .again{
      width:40%;
      margin:auto;
      display:flex;
      align-items:center;
      flex-direction:column;
    }
    h1{
      background:green;
      padding: 20px 0;
      text-align:center;
      margin:0;
    }
    body{
      background-color:lightblue;
      padding:0;
      margin:0;

    }
    .buttons {
      width:100%;
      display: flex;
      justify-content: space-evenly;
    }
  </style>
</head>
<body>
  <h1>Welcome To Our Attendance Section</h1>

  <?php if (!empty($message)) { ?>
    <!-- Display the success message and "Mark Another" button -->
    <div class="again">
      <h4><?php echo $message; ?></h4>
      <div class="buttons">
        <button onclick="location.href = 'process.php';">Mark Another</button>
        <button onclick="location.href = '?download=true';">Download Invoice</button>
      </div>
    </div>
  <?php } ?>

  <?php if (empty($message)) { ?>
    <!-- Display the form to submit attendance -->
    <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
      <?php if (!empty($data_error)) { ?>
        <p class="error">* There are some field(s) left empty. You cannot leave them blank.</p>
      <?php } ?>
      
      <!-- Input fields for name, email, and CMS ID -->
      <input type="text" name="name" placeholder="FULL NAME">
      <p class="error"><?php echo isset($data_error['name']) ? $data_error['name'] : ''; ?></p>

      <input type="text" name="email" placeholder="EMAIL ADDRESS">
      <p class="error"><?php echo isset($data_error['email']) ? $data_error['email'] : ''; ?></p>

      <input type="text" name="cms" placeholder="CMS ID">
      <p class="error"><?php echo isset($data_error['cms']) ? $data_error['cms'] : ''; ?></p>

      <button>Submit</button>
    </form>
  <?php } ?>
</body>
</html>
