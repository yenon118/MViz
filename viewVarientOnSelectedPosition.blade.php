@php
include resource_path() . '/views/system/config.blade.php';

$organism = $info['organism'];
$result_arr = $info['result_arr'];

@endphp


<head>
    <title>{{ $config_organism }}-KB</title>

    <link rel="shortcut icon" href="{{ asset('css/images/Header/kbcommons_icon.ico') }}">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
</head>

<body>
    <!-- Back button -->
    <a href="{{ route('system.tools.MViz', ['organism'=>$organism]) }}"><button> &lt; Back </button></a>
    
    <br />
    <br />

    <h3><b>Queried genotype data:</b></h3>

    @php
    if (isset($result_arr) && is_array($result_arr) && !empty($result_arr) && !is_null($result_arr)) {
        echo "<div style=\"width:auto; height:auto; border:3px solid #000; max-height:1000px; overflow:scroll;\">";
        echo "<table style=\"text-align:center; width:100%;\">";
        // Table header
        echo "<tr>";
        foreach ($result_arr[0] as $key => $value) {
            echo "<th style=\"border:1px solid black; text-align:center; min-width:80px;\">" . $key . "</th>";
        }
        echo "</tr>";
        // Table row
        for ($i = 0; $i < count($result_arr); $i++) {
            // Table row
            echo "<tr bgcolor=\"" . ($i % 2 ? "#FFFFFF" : "#DDFDD") . "\">";
            foreach ($result_arr[$i] as $key => $value) {
                echo "<td style=\"border:1px solid black; min-width:80px;\">" . $value . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
        echo "</div>";
    } else {
        echo "<p>No genotype data found!!!</p>";
    }
    @endphp


</body>


<script type="text/javascript">
</script>
