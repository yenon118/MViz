// MViz
Route::get('/system/tools/MViz/{organism}', 'System\Tools\KBCToolsMVizController@MVizPage')->name('system.tools.MViz');
Route::get('/system/tools/MViz/viewPromotersByGenes/{organism}', 'System\Tools\KBCToolsMVizController@ViewPromotersByGenesPage')->name('system.tools.MViz.viewPromotersByGenes');
Route::get('/system/tools/MViz/viewAllCNVByGenes/{organism}', 'System\Tools\KBCToolsMVizController@ViewAllCNVByGenesPage')->name('system.tools.MViz.viewAllCNVByGenes');
Route::get('/system/tools/MViz/viewCNVAndPhenotype/{organism}', 'System\Tools\KBCToolsMVizController@ViewCNVAndPhenotypePage')->name('system.tools.MViz.viewCNVAndPhenotype');
Route::get('/system/tools/MViz/viewCNVAndPhenotype/qeuryCNVAndPhenotype/{organism}', 'System\Tools\KBCToolsMVizController@QeuryCNVAndPhenotype')->name('system.tools.MViz.viewCNVAndPhenotype.qeuryCNVAndPhenotype');
Route::get('/system/tools/MViz/viewAllCNVByAccessionAndCopyNumbers/{organism}', 'System\Tools\KBCToolsMVizController@ViewAllCNVByAccessionAndCopyNumbersPage')->name('system.tools.MViz.viewAllCNVByAccessionAndCopyNumbers');
Route::get('/system/tools/MViz/viewAllCNVByChromosomeAndRegion/{organism}', 'System\Tools\KBCToolsMVizController@ViewAllCNVByChromosomeAndRegionPage')->name('system.tools.MViz.viewAllCNVByChromosomeAndRegion');
