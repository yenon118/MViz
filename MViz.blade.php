@php
include resource_path() . '/views/system/config.blade.php';

$organism = $info['organism'];
$gene_array = $info['gene_array'];
$cnvr_array = $info['cnvr_array'];

@endphp


@extends('system.header')


@section('content')

<div class="title1">mViz</div>
<br />

<h2><b>Promoter Search</b></h2>
<table width="100%" cellspacing="14" cellpadding="14">
    <tr>
        <td width="50%" align="center" valign="top" style="border:1px solid #999999; padding:10px; background-color:#f8f8f8; text-align:left;">
            <form action="{{ route('system.tools.MViz.viewPromotersByGenes', ['organism'=>$organism]) }}" method="get" target="_blank">
                <h2>Search by Gene IDs</h2><br />
                <label for="gene1"><b>Gene IDs</b></label><br />
                <span style="font-size:10pt">
                    &nbsp;(eg
                    @foreach($gene_array as $gene)
                    {{ $gene->Gene }}
                    @endforeach
                    )
                </span><br />
                <textarea id="gene1" name="gene1" rows="10" cols="40"></textarea>
                <br /><br />
                <label for="upstream_length_1"><b>Upstream length (bp)</b></label>
                <span style="font-size:10pt">(eg 2000)</span>
                <input type="text" id="upstream_length_1" name="upstream_length_1" size="40">
                <br /><br />
                <input type="submit" value="Search">
            </form>
        </td>
        <td>
        </td>
    </tr>
</table>

<br /><br />
<h2><b>Copy Number Variation Search</b></h2>
<table width="100%" cellspacing="14" cellpadding="14">
    <tr>
        <td width="50%" align="center" valign="top" style="border:1px solid #999999; padding:10px; background-color:#f8f8f8; text-align:left;">
            <form action="{{ route('system.tools.MViz.viewAllCNVByGenes', ['organism'=>$organism]) }}" method="get" target="_blank">
                <h2>Search by Gene IDs</h2><br />
                <label for="gene_id_2"><b>Gene IDs</b></label><br />
                <span style="font-size:10pt">
                    &nbsp;(eg
                    @foreach($gene_array as $gene)
                    {{ $gene->Gene }}
                    @endforeach
                    )
                </span><br />
                <textarea id="gene_id_2" name="gene_id_2" rows="15" cols="40"></textarea>
                <br /><br />
                <label for="cnv_data_option_2"><b>Data Option:</b></label>
                <select name="cnv_data_option_2" id="cnv_data_option_2">
                    <option value="Individual_Hits">Individual Hits</option>
                    <option value="Consensus_Regions" selected>Consensus Regions</option>
                </select>
                <br /><br />
                <input type="submit" value="Search">
            </form>
        </td>
        <td width="50%" align="center" valign="top" style="border:1px solid #999999; padding:10px; background-color:#f8f8f8; text-align:left;">
            <form action="{{ route('system.tools.MViz.viewAllCNVByAccessionAndCopyNumbers', ['organism'=>$organism]) }}" method="get" target="_blank">
                <h2>Search By Accession and Copy Numbers</h2><br />
                <label for="accession_2"><b>Accession:</b></label>
                <span style="font-size:10pt">
                    &nbsp;(eg
                    @foreach($cnvr_array as $cnvr)
                    {{ $cnvr->Accession }}
                    @endforeach
                    )
                </span><br />
                <input type="text" id="accession_2" name="accession_2" size="50">
                <br /><br />
                <label for="copy_number_2"><b>Copy Numbers:</b></label><br />
                <span style="font-size:10pt">
                    (eg CN0 CN1 CN2 CN3 CN4 CN5 CN6 CN7 CN8)
                </span><br />
                <textarea id="copy_number_2" name="copy_number_2" rows="12" cols="50"></textarea>
                <br /><br />
                <label for="cnv_data_option_2"><b>Data Option:</b></label>
                <select name="cnv_data_option_2" id="cnv_data_option_2">
                    <option value="Individual_Hits">Individual Hits</option>
                    <option value="Consensus_Regions" selected>Consensus Regions</option>
                </select>
                <br /><br />
                <input type="submit" value="Search">
            </form>
        </td>
    </tr>
    <tr>
        <td width="50%" align="center" valign="top" style="border:1px solid #999999; padding:10px; background-color:#f8f8f8; text-align:left;">
            <form action="{{ route('system.tools.MViz.viewAllCNVByChromosomeAndRegion', ['organism'=>$organism]) }}" method="get" target="_blank">
                <h2>Search By Chromosome and Region</h2><br />
                <label for="chromosome_2"><b>Chromosome:</b></label>
                <span style="font-size:10pt">
                    &nbsp;(eg
                    @foreach($cnvr_array as $cnvr)
                    {{ $cnvr->Chromosome }}
                    @endforeach
                    )
                </span>
                <input type="text" id="chromosome_2" name="chromosome_2" size="60">
                <br /><br />
                <label for="position_start_2"><b>Starting Position:</b></label>
                <span style="font-size:10pt">
                    &nbsp;(eg
                    @foreach($cnvr_array as $cnvr)
                    {{ $cnvr->Start }}
                    @endforeach
                    )
                </span>
                <input type="text" id="position_start_2" name="position_start_2" size="60">
                <br /><br />
                <label for="position_end_2"><b>Ending Position:</b></label>
                <span style="font-size:10pt">
                    &nbsp;(eg
                    @foreach($cnvr_array as $cnvr)
                    {{ $cnvr->End }}
                    @endforeach
                    )
                </span>
                <input type="text" id="position_end_2" name="position_end_2" size="60">
                <br /><br />
                <label for="cnv_data_option_2"><b>Data Option:</b></label>
                <select name="cnv_data_option_2" id="cnv_data_option_2">
                    <option value="Individual_Hits">Individual Hits</option>
                    <option value="Consensus_Regions" selected>Consensus Regions</option>
                </select>
                <br /><br />
                <input type="submit" value="Search">
            </form>
        </td>
        <td>
        </td>
    </tr>
</table>



@endsection


@section('javascript')

<script type="text/javascript">
    let gene_array = <?php echo json_encode($gene_array); ?>;

    // Populate gene1 textarea placeholder
    gene1_placeholder = "\nPlease separate each gene into a new line.\n\nExample:\n";
    for (let i = 0; i < gene_array.length; i++) {
        gene1_placeholder += gene_array[i]['Gene'] + "\n";
    }
    document.getElementById('gene1').placeholder = gene1_placeholder;

    // Populate gene2 textarea placeholder
    gene2_placeholder = "\nPlease separate each gene into a new line.\n\nExample:\n";
    for (let i = 0; i < gene_array.length; i++) {
        gene2_placeholder += gene_array[i]['Gene'] + "\n";
    }
    document.getElementById('gene_id_2').placeholder = gene2_placeholder;

    // Populate copy_number_2 textarea placeholder
    copy_number2_placeholder = "\nPlease separate each copy number into a new line.\n\nExample:\nCN0\nCN1\nCN3\n\n * CN2 represents normal.\n** CN2 is not in individual hits dataset.\n";
    document.getElementById('copy_number_2').placeholder = copy_number2_placeholder;
</script>

@endsection