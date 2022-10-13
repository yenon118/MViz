@php
include resource_path() . '/views/system/config.blade.php';

$organism = $info['organism'];
$chromosome = $info['chromosome'];
$position = $info['position'];
$genotype_array = $info['genotype_array'];
$phenotype = $info['phenotype'];

@endphp


<head>
    <title>{{ $config_organism }}-KB</title>

    <link rel="shortcut icon" href="{{ asset('css/images/Header/kbcommons_icon.ico') }}">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
    <script src="https://cdn.plot.ly/plotly-2.12.1.min.js"></script>
</head>

<body>

<h3>Queried CNV and Phenotype:</h3>
<div style='width:auto; height:auto; overflow:visible; max-height:1000px;'>
<table style='text-align:center; border:3px solid #000;'>
<tr>
<th style="border:1px solid black; min-width:80px;">Chromsome</th>
<th style="border:1px solid black; min-width:80px;">Position</th>
<th style="border:1px solid black; min-width:80px;">Genotype</th>
<th style="border:1px solid black; min-width:80px;">Phenotype</th>
</tr>
<tr bgcolor="#DDFFDD">
<td style="border:1px solid black; min-width:80px;">{{$chromosome}}</td>
<td style="border:1px solid black; min-width:80px;">{{$position}}</td>
<td style="border:1px solid black; min-width:80px;">{{implode(',', $genotype_array)}}</td>
<td style="border:1px solid black; min-width:80px;">{{$phenotype}}</td>
</tr>
</table>
</div>
<br /><br />

<h3>Figures:</h3>
<div id="genotype_figure_div">Loading Genotype plot...</div>

</body>

<script src="{{ asset('system/home/MViz/js/viewVariantAndPhenotypeFigures.js') }}" type="text/javascript"></script>

<script type="text/javascript" language="javascript">
    var organism = <?php if(isset($organism)) {echo json_encode($organism, JSON_INVALID_UTF8_IGNORE);} else {echo "";}?>;
    var chromosome = <?php if(isset($chromosome)) {echo json_encode($chromosome, JSON_INVALID_UTF8_IGNORE);} else {echo "";}?>;
    var position = <?php if(isset($position)) {echo json_encode($position, JSON_INVALID_UTF8_IGNORE);} else {echo "";}?>;
    var phenotype = <?php if(isset($phenotype)) {echo json_encode($phenotype, JSON_INVALID_UTF8_IGNORE);} else {echo "";}?>;
    var genotype_array = <?php if(isset($genotype_array) && is_array($genotype_array) && !empty($genotype_array)) {echo json_encode($genotype_array, JSON_INVALID_UTF8_IGNORE);} else {echo "";}?>;

    if (organism && chromosome && position && phenotype && genotype_array.length > 0) {
        $.ajax({
            url: 'qeuryVariantAndPhenotypeFigures/'+organism,
            type: 'GET',
            contentType: 'application/json',
            data: {
                Organism: organism,
                Chromosome: chromosome,
                Position: position,
                Genotype: genotype_array,
                Phenotype: phenotype
            },
            success: function (response) {
                res = JSON.parse(response);

                if (res && phenotype) {
                    var result_arr = processQueriedData(res, phenotype);

                    var genotypeData = collectDataForFigure(result_arr, phenotype, 'Genotype');

                    plotFigure(genotypeData, 'Genotype', 'genotype_figure_div');
                }
            },
            error: function (xhr, status, error) {
                console.log('Error with code ' + xhr.status + ': ' + xhr.statusText);
                document.getElementById('genotype_figure_div').innerText="";
                var p_tag = document.createElement('p');
                p_tag.innerHTML = "Genotype distribution figure is not available due to lack of data!!!";
                document.getElementById('genotype_figure_div').appendChild(p_tag);
            }
        });
    } else {
        document.getElementById('genotype_figure_div').innerText="";
        var p_tag = document.createElement('p');
        p_tag.innerHTML = "Genotype distribution figure is not available due to lack of data!!!";
        document.getElementById('genotype_figure_div').appendChild(p_tag);
    }

</script>