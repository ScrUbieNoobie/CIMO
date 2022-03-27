<?php
require 'db.php';
require 'jwt_decode.php';
require 'jwt_encoder.php';
header('Content-Type:application/json');
if(isset($_GET['token'])){
    function format_strt($text){
        $ret = strtolower($text);
        $ret1 = ucwords($ret, '[{(');
        return $ret1 . ' Street';
    }

    function format_brgy($text){
        $word = 'Barangay';
        $ret = strtolower($text);
        $ret1 = ucwords($ret, '(');
        if(strpos($ret1, $word) !== false){
            return $ret1;
        }else{
            return 'Barangay ' .$ret1;
        }
    }

    function format($text){
        $ret = strtolower($text);
        return ucwords($ret, ' [{(');
    }
    $result_array = array();
    $token = $_GET['token'];
    $key = $_GET['key'];
    $jwt = decoder($token, $key);
    if($jwt){
        $violid = $_GET['viol'];
        $estid = $_GET['id'];
        $pending = "Pending";
        $stmt = mysqli_stmt_init($conn);
        $jwt_token = json_decode(json_encode($jwt), true);
        $region = $jwt_token['data']['region'];
        $province = $jwt_token['data']['province'];
        $city = $jwt_token['data']['city'];
        $brgy = $jwt_token['data']['brgy'];
        $none = "None";
        $sql = "SELECT * FROM establishment INNER JOIN establishment_type 
        ON establishment.TypeID = establishment_type.TypeID
        INNER JOIN address
        ON establishment.AddressID = address.AddressID
        INNER JOIN capacity
        ON establishment.CapacityID = capacity.CapacityID
        INNER JOIN count
        ON establishment.CountID = count.CountID
        INNER JOIN area
        ON establishment.AreaID = area.AreaID
        INNER JOIN account
        ON establishment.AccountID = account.AccountID
        INNER JOIN establishment_type AS es
        ON establishment.TypeID = es.TypeID
        INNER JOIN coordinate 
        ON address.CoordinateID = coordinate.CoordinateID
        INNER JOIN violation 
        ON establishment.EstablishmentID = violation.EstablishmentID
        WHERE establishment.EstablishmentID = ?
        AND violation.ViolationID = ?;";
        if(!mysqli_stmt_prepare($stmt, $sql)){
            array_push($result_array, array(
                'status' => 'Info Error'
            ));
            echo json_encode($result_array, JSON_PRETTY_PRINT);
        }else{
            mysqli_stmt_bind_param($stmt, 'ss', $estid, $violid);
            if(!mysqli_stmt_execute($stmt)){
                array_push($result_array, array(
                    'status' => 'Fetching Error'
                ));
                echo json_encode($result_array, JSON_PRETTY_PRINT);
            }else{
                $res = mysqli_stmt_get_result($stmt);
                while($row = mysqli_fetch_assoc($res)){
                    $array = array(
                        'establishment-name' => $row['EstablishmentName'],
                        'establishment-type' => $row['EstablishmentType'],
                        'region' => ucwords($row['Region']),
                        'province' => format($row['Province']),
                        'city' => format($row['City']),
                        'barangay' => format_brgy($row['Barangay']),
                        'street' => format_strt($row['Street']),
                        'branch' => format($row['Branch']),
                        'latitude' => $row['Latitude'],
                        'longitude' => $row['Longitude'],
                        'logo'=> $row['EstablishmentLogo'],
                        'establishment-ID' => $row['EstablishmentID'],
                        'acc_Stats' => $row['AccountStatus'],
                        'date' => $row['DateCreated'],
                        'normal-capacity' => $row['NormalCapacity'],
                        'acc_id' => $row['AccountID'],
                        'area' => $row['SquareMeters'],
                        'viol_image' => $row['ViolationScreenCapture'],
                        'viol_video' => $row['ViolationVideoUrl'],
                        'viol_id' => $row['ViolationID'],
                    );
                    array_push($result_array, $array);
                }
                echo json_encode($result_array, JSON_PRETTY_PRINT);
            }
        }
    }else{
        array_push($result_array, array(
            'status' => 'Token Error'
        ));
        echo json_encode($result_array, JSON_PRETTY_PRINT);
    }
}
?>