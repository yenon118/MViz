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


    viewAllCNVByChromosomeAndRegion
    
    <br />
    <br />
</body>


<script type="text/javascript">
</script>
