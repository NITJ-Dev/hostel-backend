<?php
/*

// For Double Seater

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once('headers.php');
require('fpdf/fpdf.php');
require_once("db.php");




//$pdf = new \setasign\Fpdi\Fpdi();

if (isset($_POST['rollno'])) {
    $roll_no=$_POST['rollno'];




require_once('fpdi/fpdi/src/autoload.php');



// Corrected SQL query with the 'FROM' keyword
$sql = "SELECT * FROM student_form WHERE rollno='$roll_no'";

$result = mysqli_query($conn, $sql);

if ($result) {
    $data = mysqli_fetch_array($result);
} else {
    // Handle query error
    echo "Error: " . mysqli_error($conn);
}



//$photo_sql="select student_photo from student_docs where rollno='$roll_no'";

// Assuming you've connected to your database
// Fetch data from the database (for the sake of example, I'll use placeholder data)
// Replace these placeholders with actual data fetched from your database
$name = $data['full_name'];
$father_name = $data['father_name'];
$mother_name = $data['mother_name'];

$branch = $data['branch'];
$blood_group = $data['blood_group'];
$student_mobile = $data['self_mobile'];
$father_mobile =  $data['father_mobile'];
$mother_mobile =  $data['mother_mobile'];
$bro_sis_mobile =  $data['sibling_mobile'];
$guardian_mobile=$data['guardian_mobile'];
$permanent_address = $data['postal_address'];
$email_id = $data['email'];
$local_guardian_address = $data['local_guardian_address'];


// Corrected SQL query to check both requester_rollno and accepter_rollno
$room_sql = "SELECT * FROM booking WHERE requester_rollno='$roll_no' OR accepter_rollno='$roll_no'";
$result_room = mysqli_query($conn, $room_sql);

if ($result_room) {
    $room = mysqli_fetch_array($result_room);
    if ($room) {
        // Determine the roommate's roll number
        if ($room['requester_rollno'] == $roll_no) {
            $roommate_rollno = $room['accepter_rollno'];
        } else {
            $roommate_rollno = $room['requester_rollno'];
        }
        
        // Fetch roommate's details from student_form table
        $roommate_sql = "SELECT rollno, full_name, branch FROM student_form WHERE rollno='$roommate_rollno'";
        $result_roommate = mysqli_query($conn, $roommate_sql);

        if ($result_roommate) {
            $roommate = mysqli_fetch_array($result_roommate);
        } else {
            // Handle query error
            echo "Error fetching roommate details: " . mysqli_error($conn);
            $roommate = null;
        }
    } else {
        echo "No booking found for the given roll number.";
        $roommate = null;
    }
} else {
    // Handle query error
    echo "Error: " . mysqli_error($conn);
    $roommate = null;
}

// Output the details
if ($roommate) {
    $roommate_roll=$roommate['rollno'];
    $roommate_name=$roommate['full_name'];
    $roommate_branch=$roommate['branch'];
    
} 

$hostel_no=$room['hostel_name'];
$room_no=$room['room_no'];


$sql_photo="select * from student_docs where rollno='$roll_no'";
$photo_sql=mysqli_query($conn,$sql_photo);
$photo=mysqli_fetch_array($photo_sql);


$hostel_no=strtoupper($hostel_no);


$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Times', 'B', 14);
$pdf->SetMargins(20, 0, 20);
// College Logo
//$pdf->Image($photo['photo'],170,30,20);
$pdf->SetFont('Times', 'B', 14);
$pdf->Cell(0, 10, 'Dr B R AMBEDKAR NATIONAL INSTITUTE OF TECHNOLOGY JALANDHAR', 0, 1, 'C');
$pdf->SetFont('Times', '', 12);
$pdf->Cell(0, 0, 'G T Road Bye Pass, Jalandhar-144008, Punjab (India)', 0, 1, 'C');
$pdf->Cell(0, 7, str_repeat('_', 80), 0, 1, 'C');
$pdf->Ln(7);

$pdf->SetFont('Times', '', 12);
$pdf->Cell(0, 5, 'To', 0, 1);
$pdf->Cell(0, 5, 'The Warden Hostel '. $hostel_no, 0, 1);
$pdf->Cell(0, 5, 'Dr. B. R. Ambedkar National Institute of Technology, Jalandhar                                             Photo', 0, 1);
//$pdf->Image('path_to_passport_photo.jpg',150,45,30);

$pdf->Ln(3);
$pdf->SetFont('Times', '', 12);
$pdf->MultiCell(0, 5, "Sir/Madam,\n \t\t\t\t\t\t\t\tIt is requested that I may be allotted  room no {$room['room_no']} in the Hostel $hostel_no , I have carefully gone through the instructions received vide email dated 6/7/2024 as per Annexure B  and certify that I will abide by the rules and regulation of the hostel. Further, I shall also be responsible for furniture, fixtures and fittings in my room. In case of violation, I may be imposed any penalty/fine which the authority may deem fit.");

$pdf->Ln(3);
$pdf->SetFont('Times', 'B', 12);
$pdf->Cell(0, 10, '(Fill the details in capital letters)', 0, 1, 'C');

$pdf->SetFont('Times', 'B', 12);
$pdf->Cell(0, 7, "Name of the Student: $name", 0, 1);
$pdf->Cell(0, 7, "Fathers Name: $father_name", 0, 1);
$pdf->Cell(0, 7, "Mothers Name: $mother_name ", 0, 1);
$pdf->Cell(0, 7, "Roll No.: $roll_no", 0, 1);
$pdf->Cell(0, 7, "Branch: $branch", 0, 1);
$pdf->Cell(0, 7, "Room Mate Name : $roommate_name ( Roll No : $roommate_roll)", 0, 1);
$pdf->Cell(0, 7, "Room Mate Branch : $roommate_branch", 0, 1);
$pdf->Cell(0, 7, "Blood Group: $blood_group", 0, 1);
$pdf->Cell(0, 7, "Mobile No. : (S) $student_mobile   (F) $father_mobile   (M) $mother_mobile   (Bro./ Sis.) $bro_sis_mobile", 0, 1);
$pdf->MultiCell(0, 7, "Permanent Address: $permanent_address", 0, 1);
$pdf->Cell(0, 7, "E-mail ID.: $email_id", 0, 1);
$pdf->Cell(0, 7, "Local guardians address: $local_guardian_address", 0, 1);
$pdf->SetFont('Times', '', 12);
$pdf->Cell(0, 5, "\t\t\t\t\t\t\t\t\t\t\t\t\t\t(with contact no.): _______________________________", 0, 1);

$pdf->SetFont('Times', 'B', 12);
$pdf->Ln(3);
$pdf->Cell(0, 7, "Received the following items:", 0, 1);
$pdf->Cell(0, 7, "1. Bed ...................... 2. Study Table ...................... 3. Study Chair ................... 4 Fan....................", 0, 1);
$pdf->Cell(0, 7, "5. Bulbs holder/ Tube Fittings .............................. 6. Any other", 0, 1);

$pdf->Ln(10);
$pdf->Cell(0, 0, "Countersign of the parent (s) with date:                                                 Signature of Applicant", 0, 1);
$pdf->SetFont('Times', 'B', 12);
$pdf->Cell(0, 7, str_repeat('_', 80), 0, 1, 'C');
$pdf->Cell(0, 7, "(For office use only)", 0, 1, 'C');
$pdf->Ln(3);
$pdf->SetFont('Times', '', 12);
$pdf->Cell(0, 7, "Advance deposited Rs .................                                                            Room Allotted .....................", 0, 1);
$pdf->Ln(2);
$pdf->Cell(0, 7, "Date of Joining: ...................", 0, 1);
$pdf->Ln(2);
$pdf->Cell(0, 7, "Hostel Clerk", 0, 1);
$pdf->Ln(2);
$pdf->Cell(0, 7, "Warden Hostel No.", 0, 1);


//$pdf = new \setasign\Fpdi\Fpdi();
//$pageCount = $pdf->setSourceFile('page2.pdf');
//$pageId = $pdf->importPage(1, \setasign\Fpdi\PdfReader\PageBoundaries::MEDIA_BOX);
//$pdf->addPage();
//$pdf->useImportedPage($pageId, 10, 10, 180);




// $pdf->Output('F', 'temp.pdf'); // Save the generated PDF to a temporary file named 'temp.pdf'

// // Now, merge the generated PDF with another existing PDF:
// $merger = new \setasign\Fpdi\Fpdi();
// $merger->setSourceFile('temp.pdf');

// // Import all pages from the generated PDF
// for ($i = 1; $i <= $pdf->getNumPages(); $i++) {
//     $tpl = $merger->importPage($i);
//     $merger->addPage();
//     $merger->useTemplate($tpl);
// }

// // Now import and append pages from another existing PDF
// $merger->setSourceFile('page2.pdf'); // Replace 'path_to_existing_pdf.pdf' with the path to your existing PDF file
// for ($i = 1; $i <= $merger->getNumPages(); $i++) {
//     $tpl = $merger->importPage($i);
//     $merger->addPage();
//     $merger->useTemplate($tpl);
// }


















$pdf->AddPage();
$pdf->SetMargins(20, 0, 20);




$pdf->SetFont('Times', 'B', 12);
$pdf->Ln(15);
// Title
$pdf->MultiCell(0, 0, 'Annexure-U1', 0, 'R');
$pdf->MultiCell(0, 0, '___________', 0, 'R');
$pdf->Ln(5);
// Subtitle
$pdf->SetFont('Times', 'BU', 13);
$pdf->Cell(0, 0, 'UNDERTAKING BY THE HOSTEL INMATES', 0, 1, 'C');
$pdf->Ln(12);













//Content
$pdf->SetFont('Times', '', 12);
$pdf->MultiCell(0, 7, "I, " . $data['full_name'] . ", son/daughter of Mr./Ms " .$data['father_name']  . ", resident of (home address) " . $data['postal_address'] . " have gone through and understood the guidelines and protocols for staying in the Institute hostels. I understand that if I am found to have given wrong information in the declaration below or not following protocols after onboarding at the institute, I will be liable for disciplinary action.");


// // Start the string with normal style
// $pdf->SetFont('Times', '', 12);
// $pdf->Write(7, "I, ");

// // Switch to bold for the database content
// $pdf->SetFont('Times', 'B', 12);
// $pdf->Write(7, $data['name']);

// // Switch back to normal style
// $pdf->SetFont('Times', '', 12);
// $pdf->Write(7, ", son/daughter of Mr./Ms ");

// // Bold again for father's name
// $pdf->SetFont('Times', 'B', 12);
// $pdf->Write(7, $data['father_name']);

// // Continue with the rest of the content
// $pdf->SetFont('Times', '', 12);
// $pdf->Write(7, ", resident of (home address) ");

// // Bold for address
// $pdf->SetFont('Times', 'B', 12);
// $pdf->Write(7, $data['address']);

// // Rest of the text
// $pdf->SetFont('Times', '', 12);
// $pdf->Write(7, " have gone through and understood the guidelines and protocols for staying in the Institute hostels. I understand that if I am found to have given wrong information in the declaration below or not following protocols after onboarding at the institute, I will be liable for disciplinary action.");






 $pdf->Ln(5);

 $pdf->MultiCell(0, 10, "I declare that:");



// Bullet points

$pdf->MultiCell(0, 7, "\t\t\t\t\t- I am not suffering from any prolonged physical and mental illness.");
 $pdf->Ln(2);
$pdf->MultiCell(0, 7, "\t\t\t\t\t- I also understand that if I am found in violation of any hostel rules, I will be liable for \t\t\t\t\t\t\t\tappropriate disciplinary action");
 $pdf->Ln(2);
$pdf->MultiCell(0, 7, "\t\t\t\t\t- I declare that without prior approval from concerned HOD/ Chief Warden/ Hostel Warden, I \t\t\t\t\t\t\t\twill not leave the station in any case.");
 $pdf->Ln(2);
$pdf->MultiCell(0, 7, "\t\t\t\t\t- I will inform my parents / guardians, if required, in case of any exigency such as depression, \t\t\t\t\t\t\t\tmental problem, act of indiscipline, etc.");
 $pdf->Ln(2);
$pdf->MultiCell(0, 7, "\t\t\t\t\t- I understand that due to limited hostel facility, the inmates have to share the available \t\t\t\t\t\t\t\tresources in the hostel and I have no objection for the same. In case I am not comfortable \t\t\t\t\t\t\t\twith the infrastructure provided to me, I will vacate the hostel and will not involve in any \t\t\t\t\t\t\t\tkind of protest in this regard.");
//$pdf->MultiCell(0, 7, "- I understand that due to limited hostel facility, the inmates have to share the available resources in the hostel and I have no objection for the same. In case I am not comfortable with the infrastructure provided to me, I will vacate the hostel and will not involve in any kind of protest in this regard.");
$pdf->Ln(10);
// Date and Place (left blank for user input)
$pdf->MultiCell(0, 7, "\t\t\t\t\t\t\t\tDate: _________________ ");
$pdf->MultiCell(0, 10, "\t\t\t\t\t\t\t\tPlace:_________________");

$pdf->Ln(25);

$pdf->SetFont('Times', 'IB', 12);
// Signature
$pdf->MultiCell(0, 10, "Name & Signature of the Student\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\tName & Signature of the Parent/Guardian");

$pdf->AddPage();
$pdf->SetFont('Times', 'BU', 12);
$pdf->SetMargins(25, 0, 25);



$pdf->Ln(20);
// Title
$pdf->MultiCell(0, 5, 'Annexure-U2', 0, 'R');
$pdf->Ln(5);
// Subtitle
$pdf->SetFont('Times', 'BU', 12);
$pdf->MultiCell(0, 7, 'UNDERTAKING BY THE STUDENT FOR NOT OWNING AND/OR USING MOTOR DRIVEN VEHICLES IN THE INSTITUTE CAMPUS',0,'C',0,1);
$pdf->Ln(10);

// Content
$pdf->SetFont('Times', '', 12);
$pdf->MultiCell(0, 7, "I, " . $data['full_name'] . ", son/daughter of Mr./Ms " . $data['father_name'] . ", resident of (home address) " . $data['postal_address'] . " hereby submit an undertaking that");


// Bullet points
$pdf->Ln(10);
$pdf->SetFont('Times', '', 12);
$pdf->MultiCell(0, 7, "\t\t\t\t\t- I will not own or make use of motor driven vehicle for commuting inside the campus \t\t\t\t\t\t\tduring my stay in the Institute. If in case, I am found to violate the above undertaking \t\t\t\t\t\t\tmy hostel seat will stand automatically cancelled without assigning any reasons.");

$pdf->Ln(2);

$pdf->SetFont('Times', '', 12);
$pdf->MultiCell(0, 7, "\t\t\t\t\t- Any visitor in my reference bringing a vehicle would follow the guidelines for \t\t\t\t\t\t\tregistering the vehicle at the Institute main gate and the hostel office, if required and I \t\t\t\t\t\t\twould be liable for punishment for any violation on this account.");

$pdf->Ln(10);

// Date and Place (left blank for user input)
$pdf->MultiCell(0, 10, "\t\t\t\t\t\t\tDate: _________________");
$pdf->MultiCell(0, 10, "\t\t\t\t\t\t\tPlace:_________________");

$pdf->Ln(25);

$pdf->SetFont('Times', 'BI', 12);
// Signature
$pdf->MultiCell(0, 10, "Name & Signature of the Student\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\tName & Signature of the Parent/Guardian");
/*
$pdf->AddPage();
$pdf->SetMargins(20, 0, 20);


$pdf->Ln(25);
$pdf->SetFont('Times', 'BU', 12);

// Title
$pdf->MultiCell(0, 5, 'Annexure-U3', 0, 'R');
$pdf->Ln(5);

// Subtitle
$pdf->SetFont('Times', 'BU', 12);
$pdf->MultiCell(0, 7, 'UNDERTAKING BY THE PARENT / GUARDIAN',0,'C',0,1);
$pdf->Ln(5);
$pdf->MultiCell(0, 7, 'REGARDING MENTAL HEALTH OF STUDENTS & AWARENESS OF THE MEDICAL FACILITIES AT INSTITUTE HEALTH CENTRE',0,'C',0,1);
$pdf->Ln(10);

// Content
$pdf->SetFont('Times', '', 12);
$pdf->MultiCell(0, 7, "I, " . $data['father_name'] . ", Father/Mother/Guardian of Mr./Ms " .$data['full_name']  . ", resident of (home address) " . $data['postal_address'] . " hereby declare the following in respect of my ward for admission at Dr B R Ambedkar, National Institute of Technology Jalandhar.");

$pdf->Ln(5);

$pdf->MultiCell(0, 10, "I am fully aware of the following:");


// Bullet points

$pdf->MultiCell(0, 7, "\t\t\t\t\t- I am fully aware that the educational journey can bring about various challenges and \t\t\t\t\t\t\tpressures, and I understand that my child may experience stress, anxiety or other mental \t\t\t\t\t\t\thealth issues during their time at the institution.");
$pdf->Ln(3);
$pdf->MultiCell(0, 7, "\t\t\t\t\t- I recognize the potential impact of academic, social and personal pressures on my childs \t\t\t\t\t\tmental well-being. I understand that these challenges are a part of life and are not solely \t\t\t\t\t\tattributed to the educational institute.");
$pdf->Ln(3);
$pdf->MultiCell(0, 7, "\t\t\t\t\t- I acknowledge that Dr B R Ambedkar NIT Jalandhar has taken measures to provide academic \t\t\t\t\t\t\tand emotional support to students, including access to counselling services and other \t\t\t\t\t\t\tresources aimed at addressing mental health concerns.");
$pdf->Ln(3);
$pdf->MultiCell(0, 7, "\t\t\t\t\t- I agree not to hold Dr B R Ambedkar NIT Jalandhar, its administrators, teachers, staff \t\t\t\t\t\t\tmembers or any other associated parties responsible or liable for any mental health \t\t\t\t\t\t\tdifficulties, including but not limited to suicide attempts, that my child may experience during \t\t\t\t\t\t\ttheir enrollment at the institution. Despite the best efforts on part of the NIT Jalandhar, if any \t\t\t\t\t\tunforeseen event takes place to my ward, I shall not hold Institute accountable and will not \t\t\t\t\t\t\tseek any financial help or compensation for the same from any court of law.");
$pdf->Ln(3);
$pdf->MultiCell(0, 7, "\t\t\t\t\t- The Institute has a dispensary in the campus for its community and have limited medical \t\t\t\t\t\t\tfacilities, which may not be adequate for treatment of chronic or serious ailments requiring \t\t\t\t\t\t\tspecial medical infrastructure and specialization.");
$pdf->Ln(3);
$pdf->MultiCell(0, 7, "\t\t\t\t\t- In case of any medical emergency, I shall coordinate the well-being of my ward as it is the \t\t\t\t\t\t\tresponsibility of the parents / guardian to take care of their ward for treatment outside the \t\t\t\t\t\t\tInstitute campus.");
$pdf->AddPage();
$pdf->Ln(30);
$pdf->MultiCell(0, 7, "\t\t\t\t\t- I authorize the institute authorties as signatory on my behalf if required by the Doctor in the \t\t\t\t\t\t\tempaneled hospital at Jalandhar during any medical emergency or other exigency.");

$pdf->Ln(3);
$pdf->MultiCell(0, 7, "\t\t\t\t\t- I will be present physically as and when the Institute will ask me during his / her stay in case \t\t\t\t\t\t\tof any extreme exigency such as serious health issue, depression, mental problem, act of \t\t\t\t\t\t\tindiscipline, etc. ");
//$pdf->MultiCell(0, 7, "- I understand that due to limited hostel facility, the inmates have to share the available resources in the hostel and I have no objection for the same. In case I am not comfortable with the infrastructure provided to me, I will vacate the hostel and will not involve in any kind of protest in this regard.");

$pdf->Ln(5);

// Date and Place (left blank for user input)
$pdf->MultiCell(0, 10, "\t\t\t\t\t\t\tDate: _____________ ");
$pdf->MultiCell(0, 10, "\t\t\t\t\t\t\tPlace:_____________");

$pdf->Ln(20);

$pdf->SetFont('Times', 'BI', 12);
// Signature
$pdf->MultiCell(0, 10, "Name & Signature of the Parent/Guardian\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\tName & Signature of the Student");




// Undertaking Title
$pdf->SetFont('Times', 'BU', 13);
$pdf->Cell(0, 0, 'UNDERTAKING BY THE STUDENT FOR SINGLE ROOM ACCOMMODATION', 0, 1, 'C');
$pdf->Ln(12);

// Undertaking Content
$pdf->SetFont('Times', '', 12);
$pdf->MultiCell(0, 7, "I, $name, son/daughter of Mr./Ms. $father_name, resident of (home address) $permanent_address, understand that single room accommodation for the UG 2021 batch is limited and available on a first-come, first-served basis during the online allotment process. I also understand that if I am allotted single room accommodation during the online hostel allotment process, I will be required to pay an additional hostel rent fee of Rs 3000/- as per Institute norms. Furthermore, I understand that if I fail to pay the requisite hostel seat rent by 25/07/2024, the hostel authorities may cancel my single room allotment, and I will be assigned a shared seat after the completion of the online allotment process, based on availability.");

$pdf->Ln(10);

// Date and Place
$pdf->MultiCell(0, 7, "Date: ..............................");
$pdf->MultiCell(0, 7, "Place: .............................");

$pdf->Ln(25);

// Signatures
$pdf->SetFont('Times', 'IB', 12);
$pdf->MultiCell(0, 10, "Name & Signature of the Student");



$pdf->AddPage();
$pdf->SetFont('Times', 'B', 12);
$pdf->SetMargins(20, 0, 20);
$pdf->Ln(25);
// Title
//$pdf->Cell(0, 10, 'Annexure - D', 0, 1, 'C');

// Subtitle
$pdf->SetFont('Times', 'B', 14);
$pdf->Cell(0, 10, 'DOCUMENT CHECKLIST', 0, 1, 'C');
$pdf->Ln(1);
$pdf->SetFont('Times', 'B', 12);
$pdf->Cell(0, 10, '(To be filled by the Student)', 0, 1, 'C');
$pdf->Ln(4);

// Personal Information
$pdf->SetFont('Times', '', 12);
$pdf->Cell(0, 7, 'Name: ' . $name, 0, 1,'');
$pdf->Cell(0, 7, 'Roll No.: ' . $roll_no, 0, 1,'');
$pdf->Cell(0, 7, 'Department: ' . $data['branch'], 0, 1,'');
$pdf->Cell(0, 7, 'Course : B. Tech', 0, 1,'');
$pdf->Cell(0, 7, 'Email ID: ' . $data['email'], 0, 1,'');
$pdf->Ln(1);

// Date and Time placeholders
$pdf->Cell(0, 10, 'Date and Time of arrival: _______________(Date)', 0, 1,'');
$pdf->Ln(1);
$pdf->Cell(0, 10, '                                        _______________ (Time)', 0, 1,'');
$pdf->Ln(1);

// Documents Checklist
$pdf->Cell(0, 10, 'I am submitting the following documents to the hostel office:');
$pdf->Ln(10);
//$pdf->Cell(0, 10, ' 1. Admission Letter from Institute                           :  _____ (Yes / No)');
//$pdf->Ln(10); 
$pdf->Cell(0, 10, ' 1. Institute Fee Receipt (Hosteler)                          : _____ (Yes / No)');
$pdf->Ln(10);
$pdf->Cell(0, 10, ' 2. Mess Advance Fee Receipt (July - Dec 2024)    : _____ (Yes / No)');
$pdf->Ln(10);
$pdf->Cell(0, 10, ' 3. Undertakings by student/parent (Annexure - U1 & U2)      : _____ (Yes / No)');
//$pdf->Ln(10);
//$pdf->Cell(0, 10, ' 4. Undertaking by Parent (Annexure - C)               : _____ (Yes / No)');
//$pdf->Ln(10);
//$pdf->Cell(0, 10, ' 5. Aadhaar Card (Self Attested)                             : _____ (Yes / No)');
$pdf->Ln(10);
$pdf->Cell(0, 10, ' 4. No Dues from Previous Hostel                 : _____ (Yes / No)');
$pdf->Ln(10);
$pdf->Cell(0, 10, ' 5. Single Room Undertaking (optional for single accommodation)        : _____ (Yes / No)');
$pdf->Ln(20);

// Signatures
$pdf->Cell(0, 10, "Student(s) Signature with date: ______");
$pdf->Ln(10);
$pdf->Cell(0, 10, "Mobile No.: ________");
$pdf->Ln(7);
$pdf->Cell(0, 10, "___________________________________________________________________________");
$pdf->Ln(10);
// For office use only
$pdf->SetFont('Times', 'BI', 12);
$pdf->Cell(0, 10, '(for office use only)', 0, 1, 'C');
$pdf->Ln(10);
$pdf->SetFont('Times', 'BI', 12);
$pdf->Cell(0, 10, "Signature of Hostel Supervisor 1\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\tSignature of Hostel Supervisor 2");
$pdf->Ln(20);
$pdf->Cell(0, 10, "Hostel Clerk");







$pdf->Output(); // Output the merged PDF to the browser 
}
*/


?>


<?php


// =========================For Single Seater ==================================================================

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once('headers.php');
require('fpdf/fpdf.php');
require_once("db.php");




//$pdf = new \setasign\Fpdi\Fpdi();

if (isset($_POST['rollno'])) {
    $roll_no=$_POST['rollno'];




require_once('fpdi/fpdi/src/autoload.php');



// Corrected SQL query with the 'FROM' keyword
$sql = "SELECT * FROM student_form WHERE rollno='$roll_no'";

$result = mysqli_query($conn, $sql);

if ($result) {
    $data = mysqli_fetch_array($result);
} else {
    // Handle query error
    echo "Error: " . mysqli_error($conn);
}



$photo_sql="select student_photo from student_docs where rollno='$roll_no'";

// Assuming you've connected to your database
// Fetch data from the database (for the sake of example, I'll use placeholder data)
// Replace these placeholders with actual data fetched from your database
$name = $data['full_name'];
$father_name = $data['father_name'];
$mother_name = $data['mother_name'];

$branch = $data['branch'];
$blood_group = $data['blood_group'];
$student_mobile = $data['self_mobile'];
$father_mobile =  $data['father_mobile'];
$mother_mobile =  $data['mother_mobile'];
$bro_sis_mobile =  $data['sibling_mobile'];
$guardian_mobile=$data['guardian_mobile'];
$permanent_address = $data['postal_address'];
$email_id = $data['email'];
$local_guardian_address = $data['local_guardian_address'];


// Corrected SQL query to check both requester_rollno and accepter_rollno
$room_sql = "SELECT * FROM booking WHERE requester_rollno='$roll_no' OR accepter_rollno='$roll_no'";
$result_room = mysqli_query($conn, $room_sql);

if ($result_room) {
    $room = mysqli_fetch_array($result_room);
    if ($room) {
        // Determine the roommate's roll number
        if ($room['requester_rollno'] == $roll_no) {
            $roommate_rollno = $room['accepter_rollno'];
        } else {
            $roommate_rollno = $room['requester_rollno'];
        }
        
        // Fetch roommate's details from student_form table
        $roommate_sql = "SELECT rollno, full_name, branch FROM student_form WHERE rollno='$roommate_rollno'";
        $result_roommate = mysqli_query($conn, $roommate_sql);

        if ($result_roommate) {
            $roommate = mysqli_fetch_array($result_roommate);
        } else {
            // Handle query error
            echo "Error fetching roommate details: " . mysqli_error($conn);
            $roommate = null;
        }
    } else {
        echo "No booking found for the given roll number.";
        $roommate = null;
    }
} else {
    // Handle query error
    echo "Error: " . mysqli_error($conn);
    $roommate = null;
}

// Output the details
if ($roommate) {
    $roommate_roll=$roommate['rollno'];
    $roommate_name=$roommate['full_name'];
    $roommate_branch=$roommate['branch'];
    
} 

$hostel_no=$room['hostel_name'];
$room_no=$room['room_no'];


$sql_photo="select * from student_docs where rollno='$roll_no'";
$photo_sql=mysqli_query($conn,$sql_photo);
$photo=mysqli_fetch_array($photo_sql);


$hostel_no=strtoupper($hostel_no);


$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Times', 'B', 14);
$pdf->SetMargins(20, 0, 20);
// College Logo
//$pdf->Image($photo['photo'],170,30,20);
$pdf->SetFont('Times', 'B', 14);
$pdf->Cell(0, 10, 'Dr B R AMBEDKAR NATIONAL INSTITUTE OF TECHNOLOGY', 0, 1, 'C');
$pdf->SetFont('Times', '', 12);
$pdf->Cell(0, 0, 'G T Road Bye Pass, Jalandhar-144011, Punjab (India)', 0, 1, 'C');
$pdf->Cell(0, 7, str_repeat('_', 80), 0, 1, 'C');
$pdf->Ln(7);

$pdf->SetFont('Times', '', 12);
$pdf->Cell(0, 5, 'To', 0, 1);
$pdf->Cell(0, 5, 'The Warden Hostel '. $hostel_no, 0, 1);
$pdf->Cell(0, 5, 'Dr. B. R. Ambedkar National Institute of Technology, Jalandhar                                             Photo', 0, 1);
//$pdf->Image('path_to_passport_photo.jpg',150,45,30);

$pdf->Ln(3);
$pdf->SetFont('Times', '', 12);
$pdf->MultiCell(0, 5, "Sir/Madam,\n \t\t\t\t\t\t\t\tIt is requested that I may be allotted  room no {$room['room_no']} in the Hostel $hostel_no , I have carefully gone through the instructions received vide email dated 6/7/2024 as per Annexure B  and certify that I will abide by the rules and regulation of the hostel. Further, I shall also be responsible for furniture, fixtures and fittings in my room. In case of violation, I may be imposed any penalty/fine which the authority may deem fit.");

$pdf->Ln(3);
$pdf->SetFont('Times', 'B', 12);
$pdf->Cell(0, 10, '(Fill the details in capital letters)', 0, 1, 'C');

$pdf->SetFont('Times', 'B', 12);
$pdf->Cell(0, 7, "Name of the Student: $name", 0, 1);
$pdf->Cell(0, 7, "Fathers Name: $father_name", 0, 1);
$pdf->Cell(0, 7, "Mothers Name: $mother_name ", 0, 1);
$pdf->Cell(0, 7, "Roll No.: $roll_no", 0, 1);
$pdf->Cell(0, 7, "Branch: $branch", 0, 1);
//$pdf->Cell(0, 7, "Room Mate Name : $roommate_name ( Roll No : $roommate_roll)", 0, 1);
//$pdf->Cell(0, 7, "Room Mate Branch : $roommate_branch", 0, 1);
$pdf->Cell(0, 7, "Blood Group: $blood_group", 0, 1);
$pdf->Cell(0, 7, "Mobile No. : (S) $student_mobile   (F) $father_mobile   (M) $mother_mobile   (Bro./ Sis.) $bro_sis_mobile", 0, 1);
$pdf->MultiCell(0, 7, "Permanent Address: $permanent_address", 0, 1);
$pdf->Cell(0, 7, "E-mail ID.: $email_id", 0, 1);
$pdf->Cell(0, 7, "Local guardians address: $local_guardian_address", 0, 1);
$pdf->SetFont('Times', '', 12);
$pdf->Cell(0, 5, "\t\t\t\t\t\t\t\t\t\t\t\t\t\t(with contact no.): _______________________________", 0, 1);

$pdf->SetFont('Times', 'B', 12);
$pdf->Ln(3);
$pdf->Cell(0, 7, "Received the following items:", 0, 1);
$pdf->Cell(0, 7, "1. Bed ...................... 2. Study Table ...................... 3. Study Chair ................... 4 Fan....................", 0, 1);
$pdf->Cell(0, 7, "5. Bulbs holder/ Tube Fittings .............................. 6. Any other", 0, 1);

$pdf->Ln(10);
$pdf->Cell(0, 0, "Countersign of the parent (s) with date:                                                 Signature of Applicant", 0, 1);
$pdf->SetFont('Times', 'B', 12);
$pdf->Cell(0, 7, str_repeat('_', 80), 0, 1, 'C');
$pdf->Cell(0, 7, "(For office use only)", 0, 1, 'C');
$pdf->Ln(3);
$pdf->SetFont('Times', '', 12);
$pdf->Cell(0, 7, "Advance deposited Rs .................                                                            Room Allotted .....................", 0, 1);
$pdf->Ln(2);
$pdf->Cell(0, 7, "Date of Joining: ...................", 0, 1);
$pdf->Ln(2);
$pdf->Cell(0, 7, "Hostel Clerk", 0, 1);
$pdf->Ln(2);
$pdf->Cell(0, 7, "Warden Hostel No.", 0, 1);


//$pdf = new \setasign\Fpdi\Fpdi();
//$pageCount = $pdf->setSourceFile('page2.pdf');
//$pageId = $pdf->importPage(1, \setasign\Fpdi\PdfReader\PageBoundaries::MEDIA_BOX);
//$pdf->addPage();
//$pdf->useImportedPage($pageId, 10, 10, 180);




// $pdf->Output('F', 'temp.pdf'); // Save the generated PDF to a temporary file named 'temp.pdf'

// // Now, merge the generated PDF with another existing PDF:
// $merger = new \setasign\Fpdi\Fpdi();
// $merger->setSourceFile('temp.pdf');

// // Import all pages from the generated PDF
// for ($i = 1; $i <= $pdf->getNumPages(); $i++) {
//     $tpl = $merger->importPage($i);
//     $merger->addPage();
//     $merger->useTemplate($tpl);
// }

// // Now import and append pages from another existing PDF
// $merger->setSourceFile('page2.pdf'); // Replace 'path_to_existing_pdf.pdf' with the path to your existing PDF file
// for ($i = 1; $i <= $merger->getNumPages(); $i++) {
//     $tpl = $merger->importPage($i);
//     $merger->addPage();
//     $merger->useTemplate($tpl);
// }


















$pdf->AddPage();
$pdf->SetMargins(20, 0, 20);




$pdf->SetFont('Times', 'B', 12);
$pdf->Ln(15);
// Title
$pdf->MultiCell(0, 0, 'Annexure-U1', 0, 'R');
$pdf->MultiCell(0, 0, '___________', 0, 'R');
$pdf->Ln(5);
// Subtitle
$pdf->SetFont('Times', 'BU', 13);
$pdf->Cell(0, 0, 'UNDERTAKING BY THE HOSTEL INMATES', 0, 1, 'C');
$pdf->Ln(12);













//Content
$pdf->SetFont('Times', '', 12);
$pdf->MultiCell(0, 7, "I, " . $data['full_name'] . ", son/daughter of Mr./Ms " .$data['father_name']  . ", resident of (home address) " . $data['postal_address'] . " have gone through and understood the guidelines and protocols for staying in the Institute hostels. I understand that if I am found to have given wrong information in the declaration below or not following protocols after onboarding at the institute, I will be liable for disciplinary action.");


// // Start the string with normal style
// $pdf->SetFont('Times', '', 12);
// $pdf->Write(7, "I, ");

// // Switch to bold for the database content
// $pdf->SetFont('Times', 'B', 12);
// $pdf->Write(7, $data['name']);

// // Switch back to normal style
// $pdf->SetFont('Times', '', 12);
// $pdf->Write(7, ", son/daughter of Mr./Ms ");

// // Bold again for father's name
// $pdf->SetFont('Times', 'B', 12);
// $pdf->Write(7, $data['father_name']);

// // Continue with the rest of the content
// $pdf->SetFont('Times', '', 12);
// $pdf->Write(7, ", resident of (home address) ");

// // Bold for address
// $pdf->SetFont('Times', 'B', 12);
// $pdf->Write(7, $data['address']);

// // Rest of the text
// $pdf->SetFont('Times', '', 12);
// $pdf->Write(7, " have gone through and understood the guidelines and protocols for staying in the Institute hostels. I understand that if I am found to have given wrong information in the declaration below or not following protocols after onboarding at the institute, I will be liable for disciplinary action.");






 $pdf->Ln(5);

 $pdf->MultiCell(0, 10, "I declare that:");



// Bullet points

$pdf->MultiCell(0, 7, "\t\t\t\t\t- I am not suffering from any prolonged physical and mental illness.");
 $pdf->Ln(2);
$pdf->MultiCell(0, 7, "\t\t\t\t\t- I also understand that if I am found in violation of any hostel rules, I will be liable for \t\t\t\t\t\t\t\tappropriate disciplinary action");
 $pdf->Ln(2);
$pdf->MultiCell(0, 7, "\t\t\t\t\t- I declare that without prior approval from concerned HOD/ Chief Warden/ Hostel Warden, I \t\t\t\t\t\t\t\twill not leave the station in any case.");
 $pdf->Ln(2);
$pdf->MultiCell(0, 7, "\t\t\t\t\t- I will inform my parents / guardians, if required, in case of any exigency such as depression, \t\t\t\t\t\t\t\tmental problem, act of indiscipline, etc.");
 $pdf->Ln(2);
$pdf->MultiCell(0, 7, "\t\t\t\t\t- I understand that due to limited hostel facility, the inmates have to share the available \t\t\t\t\t\t\t\tresources in the hostel and I have no objection for the same. In case I am not comfortable \t\t\t\t\t\t\t\twith the infrastructure provided to me, I will vacate the hostel and will not involve in any \t\t\t\t\t\t\t\tkind of protest in this regard.");
//$pdf->MultiCell(0, 7, "- I understand that due to limited hostel facility, the inmates have to share the available resources in the hostel and I have no objection for the same. In case I am not comfortable with the infrastructure provided to me, I will vacate the hostel and will not involve in any kind of protest in this regard.");
$pdf->Ln(10);
// Date and Place (left blank for user input)
$pdf->MultiCell(0, 7, "\t\t\t\t\t\t\t\tDate: _________________ ");
$pdf->MultiCell(0, 10, "\t\t\t\t\t\t\t\tPlace:_________________");

$pdf->Ln(25);

$pdf->SetFont('Times', 'IB', 12);
// Signature
$pdf->MultiCell(0, 10, "Name & Signature of the Student\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\tName & Signature of the Parent/Guardian");

$pdf->AddPage();
$pdf->SetFont('Times', 'BU', 12);
$pdf->SetMargins(25, 0, 25);



$pdf->Ln(20);
// Title
$pdf->MultiCell(0, 5, 'Annexure-U2', 0, 'R');
$pdf->Ln(5);
// Subtitle
$pdf->SetFont('Times', 'BU', 12);
$pdf->MultiCell(0, 7, 'UNDERTAKING BY THE STUDENT FOR NOT OWNING AND/OR USING MOTOR DRIVEN VEHICLES IN THE INSTITUTE CAMPUS',0,'C',0,1);
$pdf->Ln(10);

// Content
$pdf->SetFont('Times', '', 12);
$pdf->MultiCell(0, 7, "I, " . $data['full_name'] . ", son/daughter of Mr./Ms " . $data['father_name'] . ", resident of (home address) " . $data['postal_address'] . " hereby submit an undertaking that");


// Bullet points
$pdf->Ln(10);
$pdf->SetFont('Times', '', 12);
$pdf->MultiCell(0, 7, "\t\t\t\t\t- I will not own or make use of motor driven vehicle for commuting inside the campus \t\t\t\t\t\t\tduring my stay in the Institute. If in case, I am found to violate the above undertaking \t\t\t\t\t\t\tmy hostel seat will stand automatically cancelled without assigning any reasons.");

$pdf->Ln(2);

$pdf->SetFont('Times', '', 12);
$pdf->MultiCell(0, 7, "\t\t\t\t\t- Any visitor in my reference bringing a vehicle would follow the guidelines for \t\t\t\t\t\t\tregistering the vehicle at the Institute main gate and the hostel office, if required and I \t\t\t\t\t\t\twould be liable for punishment for any violation on this account.");

$pdf->Ln(10);

// Date and Place (left blank for user input)
$pdf->MultiCell(0, 10, "\t\t\t\t\t\t\tDate: _________________");
$pdf->MultiCell(0, 10, "\t\t\t\t\t\t\tPlace:_________________");

$pdf->Ln(25);

$pdf->SetFont('Times', 'BI', 12);
// Signature
$pdf->MultiCell(0, 10, "Name & Signature of the Student\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\tName & Signature of the Parent/Guardian");


/*

$pdf->AddPage();
$pdf->SetMargins(20, 0, 20);


$pdf->Ln(25);
$pdf->SetFont('Times', 'BU', 12);

// Title
$pdf->MultiCell(0, 5, 'Annexure-U3', 0, 'R');
$pdf->Ln(5);

// Subtitle
$pdf->SetFont('Times', 'BU', 12);
$pdf->MultiCell(0, 7, 'UNDERTAKING BY THE PARENT / GUARDIAN',0,'C',0,1);
$pdf->Ln(5);
$pdf->MultiCell(0, 7, 'REGARDING MENTAL HEALTH OF STUDENTS & AWARENESS OF THE MEDICAL FACILITIES AT INSTITUTE HEALTH CENTRE',0,'C',0,1);
$pdf->Ln(10);

// Content
$pdf->SetFont('Times', '', 12);
$pdf->MultiCell(0, 7, "I, " . $data['father_name'] . ", Father/Mother/Guardian of Mr./Ms " .$data['full_name']  . ", resident of (home address) " . $data['postal_address'] . " hereby declare the following in respect of my ward for admission at Dr B R Ambedkar, National Institute of Technology Jalandhar.");

$pdf->Ln(5);

$pdf->MultiCell(0, 10, "I am fully aware of the following:");


// Bullet points

$pdf->MultiCell(0, 7, "\t\t\t\t\t- I am fully aware that the educational journey can bring about various challenges and \t\t\t\t\t\t\tpressures, and I understand that my child may experience stress, anxiety or other mental \t\t\t\t\t\t\thealth issues during their time at the institution.");
$pdf->Ln(3);
$pdf->MultiCell(0, 7, "\t\t\t\t\t- I recognize the potential impact of academic, social and personal pressures on my childs \t\t\t\t\t\tmental well-being. I understand that these challenges are a part of life and are not solely \t\t\t\t\t\tattributed to the educational institute.");
$pdf->Ln(3);
$pdf->MultiCell(0, 7, "\t\t\t\t\t- I acknowledge that Dr B R Ambedkar NIT Jalandhar has taken measures to provide academic \t\t\t\t\t\t\tand emotional support to students, including access to counselling services and other \t\t\t\t\t\t\tresources aimed at addressing mental health concerns.");
$pdf->Ln(3);
$pdf->MultiCell(0, 7, "\t\t\t\t\t- I agree not to hold Dr B R Ambedkar NIT Jalandhar, its administrators, teachers, staff \t\t\t\t\t\t\tmembers or any other associated parties responsible or liable for any mental health \t\t\t\t\t\t\tdifficulties, including but not limited to suicide attempts, that my child may experience during \t\t\t\t\t\t\ttheir enrollment at the institution. Despite the best efforts on part of the NIT Jalandhar, if any \t\t\t\t\t\tunforeseen event takes place to my ward, I shall not hold Institute accountable and will not \t\t\t\t\t\t\tseek any financial help or compensation for the same from any court of law.");
$pdf->Ln(3);
$pdf->MultiCell(0, 7, "\t\t\t\t\t- The Institute has a dispensary in the campus for its community and have limited medical \t\t\t\t\t\t\tfacilities, which may not be adequate for treatment of chronic or serious ailments requiring \t\t\t\t\t\t\tspecial medical infrastructure and specialization.");
$pdf->Ln(3);
$pdf->MultiCell(0, 7, "\t\t\t\t\t- In case of any medical emergency, I shall coordinate the well-being of my ward as it is the \t\t\t\t\t\t\tresponsibility of the parents / guardian to take care of their ward for treatment outside the \t\t\t\t\t\t\tInstitute campus.");
$pdf->AddPage();
$pdf->Ln(30);
$pdf->MultiCell(0, 7, "\t\t\t\t\t- I authorize the institute authorties as signatory on my behalf if required by the Doctor in the \t\t\t\t\t\t\tempaneled hospital at Jalandhar during any medical emergency or other exigency.");

$pdf->Ln(3);
$pdf->MultiCell(0, 7, "\t\t\t\t\t- I will be present physically as and when the Institute will ask me during his / her stay in case \t\t\t\t\t\t\tof any extreme exigency such as serious health issue, depression, mental problem, act of \t\t\t\t\t\t\tindiscipline, etc. ");
//$pdf->MultiCell(0, 7, "- I understand that due to limited hostel facility, the inmates have to share the available resources in the hostel and I have no objection for the same. In case I am not comfortable with the infrastructure provided to me, I will vacate the hostel and will not involve in any kind of protest in this regard.");

$pdf->Ln(5);

// Date and Place (left blank for user input)
$pdf->MultiCell(0, 10, "\t\t\t\t\t\t\tDate: _____________ ");
$pdf->MultiCell(0, 10, "\t\t\t\t\t\t\tPlace:_____________");

$pdf->Ln(20);

$pdf->SetFont('Times', 'BI', 12);
// Signature
$pdf->MultiCell(0, 10, "Name & Signature of the Parent/Guardian\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\tName & Signature of the Student");



$pdf->AddPage();
$pdf->SetMargins(20, 0, 20);


$pdf->Ln(25);
$pdf->SetFont('Times', 'BU', 12);

// Title
$pdf->MultiCell(0, 5, 'Annexure-U3', 0, 'R');
$pdf->Ln(5);

// Undertaking Title
$pdf->SetFont('Times', 'BU', 13);
$pdf->Cell(0, 0, 'UNDERTAKING BY THE STUDENT FOR SINGLE ROOM ACCOMMODATION', 0, 1, 'C');
$pdf->Ln(12);

// Undertaking Content
$pdf->SetFont('Times', '', 12);
$pdf->MultiCell(0, 7, "I, $name, son/daughter of Mr./Ms. $father_name, resident of (home address) $permanent_address, understand that single room accommodation for the UG 2021 batch is limited and available on a first-come, first-served basis during the online allotment process. I also understand that if I am allotted single room accommodation during the online hostel allotment process, I will be required to pay an additional hostel rent fee of Rs 3000/- as per Institute norms. Furthermore, I understand that if I fail to pay the requisite hostel seat rent by 25/07/2024, the hostel authorities may cancel my single room allotment, and I will be assigned a shared seat after the completion of the online allotment process, based on availability.");

$pdf->Ln(10);

// Date and Place
$pdf->MultiCell(0, 7, "Date: ..............................");
$pdf->MultiCell(0, 7, "Place: .............................");

$pdf->Ln(25);

// Signatures
$pdf->SetFont('Times', 'IB', 12);
$pdf->MultiCell(0, 10, "Name & Signature of the Student");



$pdf->AddPage();
$pdf->SetFont('Times', 'B', 12);
$pdf->SetMargins(20, 0, 20);
$pdf->Ln(25);
// Title
//$pdf->Cell(0, 10, 'Annexure - D', 0, 1, 'C');

// Subtitle
$pdf->SetFont('Times', 'B', 14);
$pdf->Cell(0, 10, 'DOCUMENT CHECKLIST', 0, 1, 'C');
$pdf->Ln(1);
$pdf->SetFont('Times', 'B', 12);
$pdf->Cell(0, 10, '(To be filled by the Student)', 0, 1, 'C');
$pdf->Ln(4);

// Personal Information
$pdf->SetFont('Times', '', 12);
$pdf->Cell(0, 7, 'Name: ' . $name, 0, 1,'');
$pdf->Cell(0, 7, 'Roll No.: ' . $roll_no, 0, 1,'');
$pdf->Cell(0, 7, 'Department: ' . $data['branch'], 0, 1,'');
$pdf->Cell(0, 7, 'Course : B. Tech', 0, 1,'');
$pdf->Cell(0, 7, 'Email ID: ' . $data['email'], 0, 1,'');
$pdf->Ln(1);

// Date and Time placeholders
$pdf->Cell(0, 10, 'Date and Time of arrival: _______________(Date)', 0, 1,'');
$pdf->Ln(1);
$pdf->Cell(0, 10, '                                        _______________ (Time)', 0, 1,'');
$pdf->Ln(1);

// Documents Checklist
$pdf->Cell(0, 10, 'I am submitting the following documents to the hostel office:');
$pdf->Ln(10);
//$pdf->Cell(0, 10, ' 1. Admission Letter from Institute                           :  _____ (Yes / No)');
//$pdf->Ln(10); 
$pdf->Cell(0, 10, ' 1. Institute Fee Receipt (Hosteler)                          : _____ (Yes / No)');
$pdf->Ln(10);
$pdf->Cell(0, 10, ' 2. Mess Advance Fee Receipt (July - Dec 2024)    : _____ (Yes / No)');
$pdf->Ln(10);
$pdf->Cell(0, 10, ' 3. Undertakings by student/parent (Annexure - U1 & U2)      : _____ (Yes / No)');
//$pdf->Ln(10);
//$pdf->Cell(0, 10, ' 4. Undertaking by Parent (Annexure - C)               : _____ (Yes / No)');
//$pdf->Ln(10);
//$pdf->Cell(0, 10, ' 5. Aadhaar Card (Self Attested)                             : _____ (Yes / No)');
$pdf->Ln(10);
$pdf->Cell(0, 10, ' 4. No Dues from Previous Hostel                 : _____ (Yes / No)');
$pdf->Ln(10);
$pdf->Cell(0, 10, ' 5. Single Room Undertaking (mandatory for single accommodation)        : _____ (Yes / No)');
$pdf->Ln(20);

// Signatures
$pdf->Cell(0, 10, "Student(s) Signature with date: ______");
$pdf->Ln(10);
$pdf->Cell(0, 10, "Mobile No.: ________");
$pdf->Ln(7);
$pdf->Cell(0, 10, "___________________________________________________________________________");
$pdf->Ln(10);
// For office use only
$pdf->SetFont('Times', 'BI', 12);
$pdf->Cell(0, 10, '(for office use only)', 0, 1, 'C');
$pdf->Ln(10);
$pdf->SetFont('Times', 'BI', 12);
$pdf->Cell(0, 10, "Signature of Hostel Supervisor 1\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\tSignature of Hostel Supervisor 2");
$pdf->Ln(20);
$pdf->Cell(0, 10, "Hostel Clerk");


*/




$pdf->Output(); // Output the merged PDF to the browser 
}


?>




