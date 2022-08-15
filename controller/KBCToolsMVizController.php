<?php

namespace App\Http\Controllers\System\Tools;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use App\KBCClasses\DBAdminWrapperClass;
use App\KBCClasses\DBKBCWrapperClass;

class KBCToolsMVizController extends Controller
{
    function __construct()
    {
        $this->db_kbc_wrapper = new DBKBCWrapperClass;
    }

    public function MVizPage(Request $request, $organism)
    {
        $admin_db_wapper = new DBAdminWrapperClass;

        // Database
        $db = "KBC_" . $organism;

        // Table names
        if ($organism == "Osativa") {
            $table_name = "mViz_Rice_Japonica_Motif";
            $cnvr_table_name = "mViz_Rice_Nipponbare_CNVR";
            $gff_table_name = "mViz_Rice_Nipponbare_GFF";
        } elseif ($organism == "Athaliana") {
            $table_name = "mViz_Arabidopsis_Motif";
            $cnvr_table_name = "mViz_Arabidopsis_CNVR";
            $gff_table_name = "mViz_Arabidopsis_GFF";
        } elseif ($organism == "Zmays") {
            $table_name = "mViz_Maize_Motif";
            // $cnvr_table_name = "mViz_Maize_CNVR";
            // $gff_table_name = "mViz_Maize_GFF";
        }

        if (isset($table_name) && isset($cnvr_table_name) && isset($gff_table_name)) {
            // Query gene from database
            $sql = "SELECT DISTINCT M.Gene ";
            $sql = $sql . "FROM " . $db . "." . $gff_table_name . " AS GFF ";
            $sql = $sql . "INNER JOIN " . $db . "." . $table_name . " AS M ";
            $sql = $sql . "ON M.Gene = GFF.Name ";
            $sql = $sql . "INNER JOIN " . $db . "." . $cnvr_table_name . " AS CNVR ";
            $sql = $sql . "ON CNVR.Chromosome = GFF.Chromosome AND CNVR.Start < GFF.Start AND CNVR.End > GFF.End ";
            $sql = $sql . "LIMIT 3;";
            
            $gene_array = DB::connection($db)->select($sql);
        }

        // Get one CNVR result
        if ($organism == "Osativa" || $organism == "Athaliana") {
            // Query chromosme, region, and accession from database
            $sql = "SELECT * FROM " . $db . "." . $cnvr_table_name . " LIMIT 1;";
            $cnvr_array = DB::connection($db)->select($sql);
        }

        if (isset($table_name) && isset($gene_array) && isset($cnvr_table_name) && isset($cnvr_array)) {
            // Package variables that need to go to the view
            $info = [
                'organism' => $organism,
                'gene_array' => $gene_array,
                'cnvr_array' => $cnvr_array
            ];

            // Return to view
            return view('system/tools/MViz/MViz')->with('info', $info);
        } else {
            // Package variables that need to go to the view
            $info = [
                'organism' => $organism,
            ];

            // Return to view
            return view('system/tools/MViz/MVizNotAvailable')->with('info', $info);
        }

    }

    public function ViewPromotersByGenesPage(Request $request, $organism) {

        // Database
        $db = "KBC_" . $organism;

        $gene1 = $request->gene1;
        $upstream_length_1 = $request->upstream_length_1;

        // Convert gene1 string to array
        if (is_string($gene1)) {
            $gene_arr = preg_split("/[;, \n]+/", $gene1);
            for ($i = 0; $i < count($gene_arr); $i++) {
                $gene_arr[$i] = trim($gene_arr[$i]);
            }
        } elseif (is_array($gene1)) {
            $gene_arr = $gene1;
            for ($i = 0; $i < count($gene_arr); $i++) {
                $gene_arr[$i] = trim($gene_arr[$i]);
            }
        }

        // Convert upstream length to integer
        if (is_string($upstream_length_1)) {
            $upstream_length = intval(trim($upstream_length_1));
        } elseif (is_int($upstream_length_1)) {
            $upstream_length = upstream_length_1;
        } elseif (is_float(upstream_length_1)) {
            $upstream_length = intval($upstream_length_1);
        }

        // Table names
        if ($organism == "Osativa") {
            $table_name = "mViz_Rice_Nipponbare_GFF";
            $motif_table_name = "mViz_Rice_Japonica_Motif";
            $motif_sequence_table_name = "mViz_Rice_Japonica_Motif_Sequence";
            $tf_table_name = "mViz_Rice_Japonica_TF";
        } elseif ($organism == "Athaliana") {
            $table_name = "mViz_Arabidopsis_GFF";
            $motif_table_name = "mViz_Arabidopsis_Motif";
            $motif_sequence_table_name = "mViz_Arabidopsis_Motif_Sequence";
            $tf_table_name = "mViz_Arabidopsis_TF";
        } elseif ($organism == "Zmays") {
            $table_name = "mViz_Maize_GFF";
            // $motif_table_name = "mViz_Maize_Motif";
            // $motif_sequence_table_name = "mViz_Maize_Motif_Sequence";
            // $tf_table_name = "mViz_Maize_TF";
        }

        $query_str = "SELECT * FROM " . $db . "." . $table_name . " WHERE (Name IN ('";
        for ($i = 0; $i < count($gene_arr); $i++) {
            if ($i < (count($gene_arr)-1)){
                $query_str = $query_str . $gene_arr[$i] . "', '";
            } else {
                $query_str = $query_str . $gene_arr[$i];
            }
        }
        $query_str = $query_str . "'));";
        $result_arr = DB::connection($db)->select($query_str);

        // Calculate promoter start and end
        for ($i = 0; $i < count($result_arr); $i++) {
            if ($result_arr[$i]->Strand == '+') {
                $result_arr[$i]->Promoter_End = $result_arr[$i]->Start-1;
                $result_arr[$i]->Promoter_Start = ((($result_arr[$i]->Promoter_End-$upstream_length) > 0) ? ($result_arr[$i]->Promoter_End-$upstream_length) : 1);
            } elseif ($result_arr[$i]->Strand == '-') {
                $result_arr[$i]->Promoter_Start = $result_arr[$i]->End+1;
                $result_arr[$i]->Promoter_End = $result_arr[$i]->Promoter_Start + $upstream_length;
            }
        }

        // Get motifs
        for ($i = 0; $i < count($result_arr); $i++) {
            $query_str = "
            SELECT M.Gene, MS.Chromosome, MS.Start, MS.End, MS.Strand, MS.Name AS TF, TF.TF_Family, MS.Sequence AS Consensus_Sequence FROM (
                SELECT Motif, Gene FROM " . $db . "." . $motif_table_name . " WHERE Gene = '" . $result_arr[$i]->Name . "'
            ) AS M
            INNER JOIN (
                SELECT Chromosome, Start, End, Strand, Name, Sequence FROM " . $db . "." . $motif_sequence_table_name . " 
                WHERE (Chromosome = '" . $result_arr[$i]->Chromosome . "') AND (Strand = '" . $result_arr[$i]->Strand . "') 
                AND ((Start BETWEEN " . $result_arr[$i]->Promoter_Start . " AND " . $result_arr[$i]->Promoter_End . " ) OR (End BETWEEN " . $result_arr[$i]->Promoter_Start . " AND " . $result_arr[$i]->Promoter_End . "))
            ) AS MS
            ON M.Motif = MS.Name
            LEFT JOIN " . $db . "." . $tf_table_name . " AS TF ON MS.Name = TF.TF
            ORDER BY Start, End;
            ";
            $motif_result_arr = DB::connection($db)->select($query_str);

            $result_arr[$i]->Motif_Data = $motif_result_arr;
        }

        // Package variables that need to go to the view
        $info = [
            'organism' => $organism,
            'result_arr' => $result_arr,
        ];

        // Return to view
        return view('system/tools/MViz/viewPromotersByGenes')->with('info', $info);
    }

    public function ViewAllCNVByGenesPage(Request $request, $organism) {

        // Database
        $db = "KBC_" . $organism;

        $gene_id_2 = $request->gene_id_2;
        $cnv_data_option = $request->cnv_data_option_2;

        // Convert gene_id_2 string to array
        if (is_string($gene_id_2)) {
            $gene_arr = preg_split("/[;, \n]+/", $gene_id_2);
            for ($i = 0; $i < count($gene_arr); $i++) {
                $gene_arr[$i] = trim($gene_arr[$i]);
            }
        } elseif (is_array($gene_id_2)) {
            $gene_arr = $gene_id_2;
            for ($i = 0; $i < count($gene_arr); $i++) {
                $gene_arr[$i] = trim($gene_arr[$i]);
            }
        }

        // Table names
        if ($organism == "Osativa") {
            $table_name = "mViz_Rice_Nipponbare_GFF";
            if ($cnv_data_option == "Consensus_Regions") {
                $cnv_table_name = "mViz_Rice_Nipponbare_CNVR";
            } elseif ($cnv_data_option == "Individual_Hits") {
                $cnv_table_name = "mViz_Rice_Nipponbare_CNVS";
            }
        } elseif ($organism == "Athaliana") {
            $table_name = "mViz_Arabidopsis_GFF";
            if ($cnv_data_option == "Consensus_Regions") {
                $cnv_table_name = "mViz_Arabidopsis_CNVR";
            } elseif ($cnv_data_option == "Individual_Hits") {
                $cnv_table_name = "mViz_Arabidopsis_CNVS";
            }
        } elseif ($organism == "Zmays") {
            $table_name = "mViz_Maize_GFF";
            if ($cnv_data_option == "Consensus_Regions") {
                $cnv_table_name = "mViz_Maize_CNVR";
            } elseif ($cnv_data_option == "Individual_Hits") {
                $cnv_table_name = "mViz_Maize_CNVS";
            }
        }

        // Query gene information
        $query_str = "SELECT Chromosome, Start, End, Strand, Name AS Gene_ID, Gene_Description FROM " . $db . "." . $table_name;
        $query_str = $query_str . " WHERE (Name IN ('";
        for ($i = 0; $i < count($gene_arr); $i++) {
            if ($i < (count($gene_arr)-1)){
                $query_str = $query_str . $gene_arr[$i] . "', '";
            } else {
                $query_str = $query_str . $gene_arr[$i];
            }
        }
        $query_str = $query_str . "'));";

        $gene_result_arr = DB::connection($db)->select($query_str);

        // Query CNV information
        if(isset($gene_result_arr) && is_array($gene_result_arr) && !empty($gene_result_arr)) {
            $query_str = "SELECT Chromosome, Start, End, Width, Strand, ";
            $query_str = $query_str . "COUNT(IF(CN = 'CN0', 1, null)) AS CN0, ";
            $query_str = $query_str . "COUNT(IF(CN = 'CN1', 1, null)) AS CN1, ";
            if ($cnv_data_option == "Consensus_Regions") {
                $query_str = $query_str . "COUNT(IF(CN = 'CN2', 1, null)) AS CN2, ";
            }
            $query_str = $query_str . "COUNT(IF(CN = 'CN3', 1, null)) AS CN3, ";
            $query_str = $query_str . "COUNT(IF(CN = 'CN4', 1, null)) AS CN4, ";
            $query_str = $query_str . "COUNT(IF(CN = 'CN5', 1, null)) AS CN5, ";
            $query_str = $query_str . "COUNT(IF(CN = 'CN6', 1, null)) AS CN6, ";
            $query_str = $query_str . "COUNT(IF(CN = 'CN7', 1, null)) AS CN7, ";
            $query_str = $query_str . "COUNT(IF(CN = 'CN8', 1, null)) AS CN8 ";
            $query_str = $query_str . "FROM " . $db . "." . $cnv_table_name . " WHERE ";

            for ($i = 0; $i < count($gene_result_arr); $i++) {
                if($i < (count($gene_result_arr)-1)){
                    $query_str = $query_str . "((Chromosome = '" . $gene_result_arr[$i]->Chromosome . "') AND (Start <= " . $gene_result_arr[$i]->Start . ") AND (End >= " . $gene_result_arr[$i]->End . ")) OR";
                } elseif ($i == (count($gene_result_arr)-1)) {
                    $query_str = $query_str . "((Chromosome = '" . $gene_result_arr[$i]->Chromosome . "') AND (Start <= " . $gene_result_arr[$i]->Start . ") AND (End >= " . $gene_result_arr[$i]->End . ")) ";
                }
            }

            $query_str = $query_str . "GROUP BY Chromosome, Start, End, Width, Strand ";
            $query_str = $query_str . "ORDER BY Chromosome, Start, End;";

            $cnv_result_arr = DB::connection($db)->select($query_str);
        } else {
            $cnv_result_arr = NULL;
        }

        if(isset($gene_result_arr) && is_array($gene_result_arr) && !empty($gene_result_arr) && isset($cnv_result_arr) && is_array($cnv_result_arr) && !empty($cnv_result_arr)) {
            for ($i = 0; $i < count($cnv_result_arr); $i++) {
                $query_str = "SELECT CNV.Chromosome, CNV.Start AS CNV_Start, CNV.End AS CNV_End, CNV.Width AS CNV_Width, CNV.Strand AS CNV_Strand, ";
                $query_str = $query_str . "GFF.Start AS Gene_Start, GFF.End AS Gene_End, GFF.Strand AS Gene_Strand, GFF.Name AS Gene_Name, GFF.Gene_Description ";
                $query_str = $query_str . "FROM ( ";
                $query_str = $query_str . "SELECT DISTINCT Chromosome, Start, End, Width, Strand ";
                $query_str = $query_str . "FROM " . $db . "." . $cnv_table_name . " WHERE ";
                $query_str = $query_str . "(Chromosome = '" . $cnv_result_arr[$i]->Chromosome . "') AND (Start = " . $cnv_result_arr[$i]->Start . ") AND (End = " . $cnv_result_arr[$i]->End . ") ";
                $query_str = $query_str . ") AS CNV ";
                $query_str = $query_str . "LEFT JOIN " . $db . "." . $table_name . " AS GFF ON ";
                $query_str = $query_str . "(CNV.Chromosome = GFF.Chromosome AND CNV.Start <= GFF.Start AND CNV.End >= GFF.End) ";
                $query_str = $query_str . "ORDER BY CNV.Chromosome, CNV.Start, GFF.Start, GFF.End;";

                $neighbouring_genes_result_arr = DB::connection($db)->select($query_str);

                $cnv_result_arr[$i]->Neighbouring_Genes = $neighbouring_genes_result_arr;
            }
        }

        // Package variables that need to go to the view
        $info = [
            'organism' => $organism,
            'cnv_data_option' => $cnv_data_option,
            'gene_result_arr' => $gene_result_arr,
            'cnv_result_arr' => $cnv_result_arr
        ];

        // Return to view
        return view('system/tools/MViz/viewAllCNVByGenes')->with('info', $info);
    }

    public function QeuryCNVAndPhenotype(Request $request, $organism) {

        // Database
        $db = "KBC_" . $organism;

        $chromosome = $request->Chromosome;
        $position_start = $request->Start;
        $position_end = $request->End;
        $cnv_data_option = $request->Data_Option;
        $cn = $request->CN;
        $phenotype = $request->Phenotype;

        // Convert copy number string to array
        if (is_string($cn)) {
            $cn_array = preg_split("/[;, \n]+/", $cn);
            for ($i = 0; $i < count($cn_array); $i++) {
                $cn_array[$i] = trim($cn_array[$i]);
            }
        } elseif (is_array($cn)) {
            $cn_array = $cn;
            for ($i = 0; $i < count($cn_array); $i++) {
                $cn_array[$i] = trim($cn_array[$i]);
            }
        }

        // Convert phenotype string to array
        if (is_string($phenotype)) {
            $phenotype_array = preg_split("/[;, \n]+/", $phenotype);
            for ($i = 0; $i < count($phenotype_array); $i++) {
                $phenotype_array[$i] = trim($phenotype_array[$i]);
            }
        } elseif (is_array($phenotype)) {
            $phenotype_array = $phenotype;
            for ($i = 0; $i < count($phenotype_array); $i++) {
                $phenotype_array[$i] = trim($phenotype_array[$i]);
            }
        }

        // Table names
        if ($organism == "Osativa") {
            $table_name = "mViz_Rice_Nipponbare_GFF";
            if ($cnv_data_option == "Consensus_Regions") {
                $cnv_table_name = "mViz_Rice_Nipponbare_CNVR";
            } elseif ($cnv_data_option == "Individual_Hits") {
                $cnv_table_name = "mViz_Rice_Nipponbare_CNVS";
            }
        } elseif ($organism == "Athaliana") {
            $table_name = "mViz_Arabidopsis_GFF";
            if ($cnv_data_option == "Consensus_Regions") {
                $cnv_table_name = "mViz_Arabidopsis_CNVR";
            } elseif ($cnv_data_option == "Individual_Hits") {
                $cnv_table_name = "mViz_Arabidopsis_CNVS";
            }
        } elseif ($organism == "Zmays") {
            $table_name = "mViz_Maize_GFF";
            if ($cnv_data_option == "Consensus_Regions") {
                $cnv_table_name = "mViz_Maize_CNVR";
            } elseif ($cnv_data_option == "Individual_Hits") {
                $cnv_table_name = "mViz_Maize_CNVS";
            }
        }

        // Query string
        $query_str = "SELECT CNV.Chromosome, CNV.Start, CNV.End, CNV.Width, CNV.Strand, CNV.Accession, CNV.CN, ";
        $query_str = $query_str . "CASE CNV.CN ";
        $query_str = $query_str . "WHEN 'CN0' THEN 'Loss' ";
        $query_str = $query_str . "WHEN 'CN1' THEN 'Loss' ";
        $query_str = $query_str . "WHEN 'CN3' THEN 'Gain' ";
        $query_str = $query_str . "WHEN 'CN4' THEN 'Gain' ";
        $query_str = $query_str . "WHEN 'CN5' THEN 'Gain' ";
        $query_str = $query_str . "WHEN 'CN6' THEN 'Gain' ";
        $query_str = $query_str . "WHEN 'CN7' THEN 'Gain' ";
        $query_str = $query_str . "WHEN 'CN8' THEN 'Gain' ";
        $query_str = $query_str . "ELSE 'Normal' ";
        $query_str = $query_str . "END as Status ";
        if (isset($phenotype_array) && is_array($phenotype_array) && !empty($phenotype_array)) {
            for ($i = 0; $i < count($phenotype_array); $i++) {
                $query_str = $query_str . ", G." . $phenotype_array[$i] . " ";
            }
        }
        $query_str = $query_str . "FROM " . $db . "." . $cnv_table_name . " AS CNV ";
        if (isset($phenotype_array) && is_array($phenotype_array) && !empty($phenotype_array)) {
            $query_str = $query_str . "LEFT JOIN soykb.germplasm AS G ";
            $query_str = $query_str . "ON AM.GRIN_Accession = G.ACNO ";
        }
        $query_str = $query_str . "WHERE (CNV.Chromosome = '" . $chromosome . "') ";
        $query_str = $query_str . "AND (CNV.Start BETWEEN " . $position_start . " AND " . $position_end . ") ";
        $query_str = $query_str . "AND (CNV.End BETWEEN " . $position_start . " AND " . $position_end . ") ";
        if (count($cn_array) > 0) {
            $query_str = $query_str . "AND (CNV.CN IN ('";
            for ($i = 0; $i < count($cn_array); $i++) {
                if($i < (count($cn_array)-1)){
                    $query_str = $query_str . trim($cn_array[$i]) . "', '";
                } elseif ($i == (count($cn_array)-1)) {
                    $query_str = $query_str . trim($cn_array[$i]);
                }
            }
            $query_str = $query_str . "')) ";
        }
        $query_str = $query_str . "ORDER BY CNV.CN, CNV.Chromosome, CNV.Start, CNV.End, CNV.Accession; ";

        $result_arr = DB::connection($db)->select($query_str);

        return json_encode($result_arr);
    }

    public function ViewCNVAndPhenotypePage(Request $request, $organism) {

        // Database
        $db = "KBC_" . $organism;

        $chromosome = $request->Chromosome;
        $position_start = $request->Position_Start;
        $position_end = $request->Position_End;
        $cnv_data_option = $request->CNV_Data_Option;

        // Package variables that need to go to the view
        $info = [
            'organism' => $organism,
            'chromosome' => $chromosome,
            'position_start' => $position_start,
            'position_end' => $position_end,
            'cnv_data_option' => $cnv_data_option,
        ];

        // Return to view
        return view('system/tools/MViz/viewCNVAndPhenotype')->with('info', $info);
    }

    public function ViewAllCNVByAccessionAndCopyNumbersPage(Request $request, $organism) {

        // Database
        $db = "KBC_" . $organism;

        $accession = $request->accession_2;
        $copy_number_2 = $request->copy_number_2;
        $cnv_data_option = $request->cnv_data_option_2;

        // Convert copy number string to array
        if (is_string($copy_number_2)) {
            $copy_number_arr = preg_split("/[;, \n]+/", $copy_number_2);
            for ($i = 0; $i < count($copy_number_arr); $i++) {
                $copy_number_arr[$i] = trim($copy_number_arr[$i]);
            }
        } elseif (is_array($copy_number_2)) {
            $copy_number_arr = $copy_number_2;
            for ($i = 0; $i < count($copy_number_arr); $i++) {
                $copy_number_arr[$i] = trim($copy_number_arr[$i]);
            }
        }

        // Table names
        if ($organism == "Osativa") {
            $table_name = "mViz_Rice_Nipponbare_GFF";
            if ($cnv_data_option == "Consensus_Regions") {
                $cnv_table_name = "mViz_Rice_Nipponbare_CNVR";
            } elseif ($cnv_data_option == "Individual_Hits") {
                $cnv_table_name = "mViz_Rice_Nipponbare_CNVS";
            }
        } elseif ($organism == "Athaliana") {
            $table_name = "mViz_Arabidopsis_GFF";
            if ($cnv_data_option == "Consensus_Regions") {
                $cnv_table_name = "mViz_Arabidopsis_CNVR";
            } elseif ($cnv_data_option == "Individual_Hits") {
                $cnv_table_name = "mViz_Arabidopsis_CNVS";
            }
        } elseif ($organism == "Zmays") {
            $table_name = "mViz_Maize_GFF";
            if ($cnv_data_option == "Consensus_Regions") {
                $cnv_table_name = "mViz_Maize_CNVR";
            } elseif ($cnv_data_option == "Individual_Hits") {
                $cnv_table_name = "mViz_Maize_CNVS";
            }
        }

        // Get CNV data
        $query_str = "SELECT CNV.Chromosome, CNV.Start, CNV.End, CNV.Width, CNV.Strand, CNV.Accession, CNV.CN ";
        $query_str = $query_str . "FROM " . $db . "." . $cnv_table_name . " AS CNV ";
        $query_str = $query_str . "WHERE (CNV.Accession = '" . $accession . "') AND (CNV.CN IN ('";
        for ($i = 0; $i < count($copy_number_arr); $i++) {
            if($i < (count($copy_number_arr)-1)){
                $query_str = $query_str . trim($copy_number_arr[$i]) . "', '";
            } elseif ($i == (count($copy_number_arr)-1)) {
                $query_str = $query_str . trim($copy_number_arr[$i]);
            }
        }
        $query_str = $query_str . "')) ";
        $query_str = $query_str . "ORDER BY CNV.CN, CNV.Chromosome, CNV.Start, CNV.End; ";

        $cnv_result_arr = DB::connection($db)->select($query_str);

        // Package variables that need to go to the view
        $info = [
            'organism' => $organism,
            'accession' => $accession,
            'cnv_result_arr' => $cnv_result_arr,
        ];

        // Return to view
        return view('system/tools/MViz/viewAllCNVByAccessionAndCopyNumbers')->with('info', $info);
    }

    public function ViewAllCNVByChromosomeAndRegionPage(Request $request, $organism) {

        // Database
        $db = "KBC_" . $organism;

        $chromosome = $request->chromosome_2;
        $position_start = $request->position_start_2;
        $position_end = $request->position_end_2;
        $cnv_data_option = $request->cnv_data_option_2;

        $chromosome = trim($chromosome);
        $position_start = intval(trim($position_start))-1;
        $position_end = intval(trim($position_end))+1;
        $cnv_data_option = trim($cnv_data_option);

        // Table names
        if ($organism == "Osativa") {
            $table_name = "mViz_Rice_Nipponbare_GFF";
            if ($cnv_data_option == "Consensus_Regions") {
                $cnv_table_name = "mViz_Rice_Nipponbare_CNVR";
            } elseif ($cnv_data_option == "Individual_Hits") {
                $cnv_table_name = "mViz_Rice_Nipponbare_CNVS";
            }
        } elseif ($organism == "Athaliana") {
            $table_name = "mViz_Arabidopsis_GFF";
            if ($cnv_data_option == "Consensus_Regions") {
                $cnv_table_name = "mViz_Arabidopsis_CNVR";
            } elseif ($cnv_data_option == "Individual_Hits") {
                $cnv_table_name = "mViz_Arabidopsis_CNVS";
            }
        } elseif ($organism == "Zmays") {
            $table_name = "mViz_Maize_GFF";
            if ($cnv_data_option == "Consensus_Regions") {
                $cnv_table_name = "mViz_Maize_CNVR";
            } elseif ($cnv_data_option == "Individual_Hits") {
                $cnv_table_name = "mViz_Maize_CNVS";
            }
        }

        $query_str = "SELECT CNV.Chromosome, CNV.Start, CNV.End, CNV.Width, CNV.Strand, ";
        $query_str = $query_str . "COUNT(IF(CNV.CN = 'CN0', 1, null)) AS CN0, ";
        $query_str = $query_str . "COUNT(IF(CNV.CN = 'CN1', 1, null)) AS CN1, ";
        if ($cnv_data_option == "Consensus_Regions") {
            $query_str = $query_str . "COUNT(IF(CNV.CN = 'CN2', 1, null)) AS CN2, ";
        }
        $query_str = $query_str . "COUNT(IF(CNV.CN = 'CN3', 1, null)) AS CN3, ";
        $query_str = $query_str . "COUNT(IF(CNV.CN = 'CN4', 1, null)) AS CN4, ";
        $query_str = $query_str . "COUNT(IF(CNV.CN = 'CN5', 1, null)) AS CN5, ";
        $query_str = $query_str . "COUNT(IF(CNV.CN = 'CN6', 1, null)) AS CN6, ";
        $query_str = $query_str . "COUNT(IF(CNV.CN = 'CN7', 1, null)) AS CN7, ";
        $query_str = $query_str . "COUNT(IF(CNV.CN = 'CN8', 1, null)) AS CN8 ";
        $query_str = $query_str . "FROM " . $db . "." . $cnv_table_name . " AS CNV ";
        $query_str = $query_str . "WHERE (CNV.Chromosome = '" . $chromosome . "') ";
        $query_str = $query_str . "AND (CNV.Start BETWEEN " . $position_start . " AND " . $position_end . ") ";
        $query_str = $query_str . "AND (CNV.End BETWEEN " . $position_start . " AND " . $position_end . ") ";
        $query_str = $query_str . "GROUP BY CNV.Chromosome, CNV.Start, CNV.End, CNV.Width, CNV.Strand ";
        $query_str = $query_str . "ORDER BY CNV.Chromosome, CNV.Start, CNV.End; ";

        $cnv_accession_count_result_arr = DB::connection($db)->select($query_str);

        if (isset($cnv_accession_count_result_arr) && is_array($cnv_accession_count_result_arr) && !empty($cnv_accession_count_result_arr)) {
            $cnv_result_arr = array();
            for ($i = 0; $i < count($cnv_accession_count_result_arr); $i++) {
                // Get CNV data
                $query_str = "SELECT CNV.Chromosome, CNV.Start, CNV.End, CNV.Width, CNV.Strand, CNV.Accession, CNV.CN ";
                $query_str = $query_str . "FROM " . $db . "." . $cnv_table_name . " AS CNV ";
                $query_str = $query_str . "WHERE (CNV.Chromosome = '" . $cnv_accession_count_result_arr[$i]->Chromosome . "') ";
                $query_str = $query_str . "AND (CNV.Start BETWEEN " . $cnv_accession_count_result_arr[$i]->Start . " AND " . $cnv_accession_count_result_arr[$i]->End . ") ";
                $query_str = $query_str . "AND (CNV.End BETWEEN " . $cnv_accession_count_result_arr[$i]->Start . " AND " . $cnv_accession_count_result_arr[$i]->End . ") ";
                $query_str = $query_str . "ORDER BY CNV.CN, CNV.Accession; ";

                $result_arr = DB::connection($db)->select($query_str);

                array_push($cnv_result_arr, $result_arr);
            }
        } else {
            $cnv_result_arr = NULL;
        }

        // Package variables that need to go to the view
        $info = [
            'organism' => $organism,
            'cnv_accession_count_result_arr' => $cnv_accession_count_result_arr,
            'cnv_result_arr' => $cnv_result_arr,
        ];

        // Return to view
        return view('system/tools/MViz/viewAllCNVByChromosomeAndRegion')->with('info', $info);
    }
}