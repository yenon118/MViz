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
        } elseif ($organism == "Athaliana") {
            $table_name = "mViz_Arabidopsis_Motif";
        } elseif ($organism == "Zmays") {
            $table_name = "mViz_Maize_Motif";
        }

        if (isset($table_name)) {
            // Query gene from database
            $sql = "SELECT DISTINCT Gene FROM " . $db . "." . $table_name . " WHERE Gene IS NOT NULL ORDER BY Gene LIMIT 3;";
            $gene_array = DB::connection($db)->select($sql);
        }

        // Get one CNVR result
        if ($organism == "Osativa") {
            $cnvr_table_name = "mViz_Rice_Nipponbare_CNVR";

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
        } elseif ($organism == "Zmays") {
            $table_name = "mViz_Maize_GFF";
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
            SELECT MS.Chromosome, MS.Start, MS.End, MS.Strand, MS.Name AS Motif, TF.TF_Family, MS.Sequence, M.Gene FROM (
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

    public function ViewAllCNVByAccessionAndCopyNumbersPage(Request $request, $organism) {

        // Database
        $db = "KBC_" . $organism;

        // Package variables that need to go to the view
        $info = [
            'organism' => $organism,
        ];

        // Return to view
        return view('system/tools/MViz/viewAllCNVByAccessionAndCopyNumbers')->with('info', $info);
    }

    public function ViewAllCNVByChromosomeAndRegionPage(Request $request, $organism) {

        // Database
        $db = "KBC_" . $organism;

        // Package variables that need to go to the view
        $info = [
            'organism' => $organism,
        ];

        // Return to view
        return view('system/tools/MViz/viewAllCNVByChromosomeAndRegion')->with('info', $info);
    }
}