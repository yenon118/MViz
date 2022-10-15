function processQueriedData(jsonObject, phenotype) {

    let idx_array = [];
    for (let i = 0; i < jsonObject.length; i++) {
        if (jsonObject[i][phenotype] == "" || jsonObject[i][phenotype] === null || jsonObject[i][phenotype] == "null" || jsonObject[i][phenotype] == "-" || jsonObject[i][phenotype] == "_" || jsonObject[i][phenotype] == "NA") {
            idx_array.push(i);
        }
    }
    idx_array.reverse();
    for (let i = 0; i < idx_array.length; i++) {
        jsonObject.splice(idx_array[i],1);
    }

    for (let i = 0; i < jsonObject.length; i++) {
        if (Array.isArray(jsonObject[i][phenotype]) || typeof jsonObject[i][phenotype] === 'string' || jsonObject[i][phenotype] instanceof String) {
            if (jsonObject[i][phenotype].includes(',')) {
                var element = jsonObject[i];
                var phenotype_array = element[phenotype].split(",");
                // Remove duplicates
                var unique_phenotype_array = phenotype_array.filter(function(c, index) {
                    return phenotype_array.indexOf(c) === index;
                });
                // Add new records to the array
                for (let j = 0; j < unique_phenotype_array.length; j++) {
                    if (j === 0) {
                        element[phenotype] = unique_phenotype_array[j];
                    } else {
                        var new_element = JSON.parse(JSON.stringify(element));
                        new_element[phenotype] = unique_phenotype_array[j];
                        jsonObject.push(new_element);
                    }
                }
            }
        }
    }

    return jsonObject;
}


function summarizeQueriedData(jsonObject, phenotype, selectedKey, isFloat){
    var summaryObject = {};

    // Get accession count
    var selectedKeyArray = [];
    for (let i = 0; i < jsonObject.length; i++) {
        if (jsonObject[i][selectedKey] != undefined && jsonObject[i][selectedKey] != null && jsonObject[i][selectedKey] != "" && jsonObject[i][selectedKey] != "null") {
            if (!selectedKeyArray.includes(jsonObject[i][selectedKey])) {
                selectedKeyArray.push(jsonObject[i][selectedKey]);
            }
        }
    }
    selectedKeyArray.sort();
    for (let i = 0; i < selectedKeyArray.length; i++) {
        summaryObject[selectedKeyArray[i]] = {
            "Total_Number_of_Phenotype": 0,
            "Number_of_Accession_with_Phenotype": 0,
            "Number_of_Accession_without_Phenotype": 0,
        };
    }
    var accessionArray = [];
    for (let i = 0; i < jsonObject.length; i++) {
        if(jsonObject[i][selectedKey] != null && jsonObject[i]["Accession"] != null) {
            if (!accessionArray.includes(jsonObject[i]["Accession"])) {
                if (jsonObject[i][phenotype] == "" || jsonObject[i][phenotype] === null || jsonObject[i][phenotype] == "null" || jsonObject[i][phenotype] == "-" || jsonObject[i][phenotype] == "_" || jsonObject[i][phenotype] == "NA") {
                    summaryObject[jsonObject[i][selectedKey]]["Number_of_Accession_without_Phenotype"] += 1;
                } else {
                    summaryObject[jsonObject[i][selectedKey]]["Number_of_Accession_with_Phenotype"] += 1;
                }
                accessionArray.push(jsonObject[i]["Accession"]);
            }
        }
    }

    // Process data
    jsonObject = processQueriedData(jsonObject, phenotype);

    if (isFloat) {
        for (let i = 0; i < jsonObject.length; i++) {
            if (jsonObject[i][selectedKey] != undefined && jsonObject[i][selectedKey] != null && jsonObject[i][selectedKey] != "" && jsonObject[i][selectedKey] != "null") {
                if (jsonObject[i][phenotype] != undefined && jsonObject[i][phenotype] != null && jsonObject[i][phenotype] != "" && jsonObject[i][phenotype] != "null") {
                    summaryObject[jsonObject[i][selectedKey]]["Total_Number_of_Phenotype"] += 1;
                }
            }
        }
    } else {
        // Get phenotype count
        var phenotypeCountColumnArray = [];
        var phenotypePercentColumnArray = [];
        for (let i = 0; i < jsonObject.length; i++) {
            if (jsonObject[i][phenotype] != undefined && jsonObject[i][phenotype] != null && jsonObject[i][phenotype] != "" && jsonObject[i][phenotype] != "null") {
                countColumnName = "Count_of_" + jsonObject[i][phenotype];
                percentColumnName = "Percent_of_" + jsonObject[i][phenotype];
                if (!phenotypeCountColumnArray.includes(countColumnName)) {
                    phenotypeCountColumnArray.push(countColumnName);
                }
                if (!phenotypePercentColumnArray.includes(percentColumnName)) {
                    phenotypePercentColumnArray.push(percentColumnName);
                }
            }
        }
        phenotypeCountColumnArray.sort();
        phenotypePercentColumnArray.sort();
        for (let i = 0; i < selectedKeyArray.length; i++) {
            for (let j = 0; j < phenotypeCountColumnArray.length; j++) {
                summaryObject[selectedKeyArray[i]][phenotypeCountColumnArray[j]] = 0;
                summaryObject[selectedKeyArray[i]][phenotypePercentColumnArray[j]] = 0;
            }
        }
        for (let i = 0; i < jsonObject.length; i++) {
            if (jsonObject[i][selectedKey] != undefined && jsonObject[i][selectedKey] != null && jsonObject[i][selectedKey] != "" && jsonObject[i][selectedKey] != "null") {
                if (jsonObject[i][phenotype] != undefined && jsonObject[i][phenotype] != null && jsonObject[i][phenotype] != "" && jsonObject[i][phenotype] != "null") {
                    countColumnName = "Count_of_" + jsonObject[i][phenotype];
                    percentColumnName = "Percent_of_" + jsonObject[i][phenotype];
                    summaryObject[jsonObject[i][selectedKey]][countColumnName] += 1;
                    summaryObject[jsonObject[i][selectedKey]][percentColumnName] += 1;
                    summaryObject[jsonObject[i][selectedKey]]["Total_Number_of_Phenotype"] += 1;
                }
            }
        }

        // Calculate percentage
        for (let i = 0; i < selectedKeyArray.length; i++) {
            for (let j = 0; j < phenotypePercentColumnArray.length; j++) {
                summaryObject[selectedKeyArray[i]][phenotypePercentColumnArray[j]] =  100 * summaryObject[selectedKeyArray[i]][phenotypePercentColumnArray[j]] / summaryObject[selectedKeyArray[i]]["Total_Number_of_Phenotype"];
                if (summaryObject[selectedKeyArray[i]][phenotypePercentColumnArray[j]] > 0) {
                    summaryObject[selectedKeyArray[i]][phenotypePercentColumnArray[j]] = Math.round(summaryObject[selectedKeyArray[i]][phenotypePercentColumnArray[j]] * 100) / 100;
                }
            }
        }
    }
    
    // Convert dict to array
    var summaryArray = [];
    for (let i = 0; i < selectedKeyArray.length; i++) {
        var columnKeys = Object.keys(summaryObject[selectedKeyArray[i]]);
        var temp_dict = {};
        temp_dict[selectedKey] = selectedKeyArray[i];
        for (let j = 0; j < columnKeys.length; j++) {
            temp_dict[columnKeys[j]] = summaryObject[selectedKeyArray[i]][columnKeys[j]];
        }
        summaryArray.push(temp_dict);
    }

    return summaryArray;
}


function constructInfoTable(res) {

    // Create table
    let detail_table = document.createElement("table");
    detail_table.setAttribute("style", "text-align:center; border:3px solid #000;");
    let detail_header_tr = document.createElement("tr");

    let header_array = Object.keys(res[0]);
    for (let i = 0; i < header_array.length; i++) {
        var detail_th = document.createElement("th");
        detail_th.setAttribute("style", "border:1px solid black; min-width:80px; height:18.5px;");
        detail_th.innerHTML = header_array[i];
        detail_header_tr.appendChild(detail_th);
    }

    detail_table.appendChild(detail_header_tr);

    for (let i = 0; i < res.length; i++) {
        var detail_tr = document.createElement("tr");
        detail_tr.style.backgroundColor = ((i%2) ? "#FFFFFF" : "#DDFFDD");
        for (let j = 0; j < header_array.length; j++) {
            var detail_td = document.createElement("td");
            detail_td.setAttribute("style", "border:1px solid black; min-width:80px; height:18.5px;");
            detail_td.innerHTML = res[i][header_array[j]];
            detail_tr.appendChild(detail_td);
        }
        detail_table.appendChild(detail_tr);
    }

    return detail_table;
}


function collectDataForFigure(jsonObject, phenotype, selectedKey) {

    var dict = {};
    var isFloat = true;
    const specialChars = /[`!@#$%^&*()_+\-=\[\]{};':"\\|,<>\/?~]/;

    for (let i = 0; i < jsonObject.length; i++) {
        var val = jsonObject[i][phenotype];
        // Trim data if it is a string
        if (typeof val === 'string' || val instanceof String) {
            val = val.trim();
        }
        // Parse value to float if possible
        if (!isNaN(parseFloat(val))){
            if (!specialChars.test(val)){
                val = parseFloat(val)
            } else {
                isFloat = false
            }
        } else {
            isFloat = false
        }
        // Add data into dictionary
        if ((!(jsonObject[i][selectedKey] === undefined)) && (jsonObject[i][selectedKey] != null) && (jsonObject[i][selectedKey] != "null") && (jsonObject[i][selectedKey] != "")) {
            if (!(dict.hasOwnProperty(jsonObject[i][selectedKey]))) {
                dict[jsonObject[i][selectedKey]] = [val];
            } else {
                dict[jsonObject[i][selectedKey]].push(val);
            }
        }
    }

    return {'Data':dict, 'IsFloat':isFloat};
}


function plotFigure(jsonObject, keyColumn, title, divID) {

    var data = [];
    var keys = Object.keys(jsonObject['Data']);

    if (jsonObject['IsFloat']){
        xAxisTitle = "Phenotype Measurement";
        yAxisTitle = keyColumn;
        // Update title
        title = title + " Box Plot";
        // Format the data to fit figure requirements
        if (jsonObject['Data']) {
            for (let i = 0; i < keys.length; i++) {
                if (jsonObject['Data'][keys[i]]) {
                    if (jsonObject['Data'][keys[i]].length > 0) {
                        data.push({
                            x: jsonObject['Data'][keys[i]],
                            type: 'violin',
                            name: keys[i],
                            box: {
                                visible: true
                            },
                            meanline: {
                                visible: true
                            },
                            boxpoints: 'Outliers',
                            boxmean: true
                        })
                    }
                }
            }
        }
        // Create layout
        var layout = {
            title: title,
            xaxis: {
                title: {
                    text: xAxisTitle
                },
                zeroline: false
            },
            yaxis: {
                title: {
                    text: yAxisTitle
                },
                fixedrange: true,
                zeroline: false
            }
        };
        // Adjust configuration
        var config = {
            toImageButtonOptions: {
                format: 'png', // one of png, svg, jpeg, webp
                filename: title
            }
        };
        // Plot figure
        if (data && layout) {
            document.getElementById(divID).innerText="";
            document.getElementById(divID).innerHTML="";
            if (data.length > 0) {
                Plotly.newPlot(divID, data, layout, config);
            } else {
                var p_tag = document.createElement('p');
                p_tag.innerHTML = title + " is not available due to lack of data!!!";
                document.getElementById(divID).appendChild(p_tag);
            }
        }
    } else {
        xAxisTitle = keyColumn;
        yAxisTitle = "Accession Count";
        // Update title
        title = title + " Bar Plot";
        // Reformat data for bar plot
        var barData = {};
        if (jsonObject['Data']) {
            for (let i = 0; i < keys.length; i++) {
                if (jsonObject['Data'][keys[i]]) {
                    if (jsonObject['Data'][keys[i]].length > 0) {
                        for (let j = 0; j < jsonObject['Data'][keys[i]].length; j++) {
                            // Add keys to barData
                            if (!(barData.hasOwnProperty(jsonObject['Data'][keys[i]][j]))) {
                                barData[jsonObject['Data'][keys[i]][j]] = {}
                                for (let k = 0; k < keys.length; k++) {
                                    barData[jsonObject['Data'][keys[i]][j]][keys[k]] = 0;
                                }
                            }
                            // Increase the count
                            barData[jsonObject['Data'][keys[i]][j]][keys[i]] += 1;
                        }
                    }
                }
            }
        }
        // Format the data to fit figure requirements
        if (Object.keys(barData).length > 0) {
            for (let i = 0; i < Object.keys(barData).length; i++) {
                // Collect all counts
                var count_array = []
                for (let j = 0; j < keys.length; j++) {
                    count_array.push(barData[Object.keys(barData)[i]][keys[j]])
                }
                data.push({
                    x: keys,
                    y: count_array,
                    type: 'bar',
                    name: Object.keys(barData)[i]
                })

            }
        }
        // Create layout
        var layout = {
            title: title,
            barmode: 'group',
            xaxis: {
                title: {
                    text: xAxisTitle
                },
                fixedrange: true
            },
            yaxis: {
                title: {
                    text: yAxisTitle
                }
            }
        };
        // Adjust configuration
        var config = {
            toImageButtonOptions: {
                format: 'png', // one of png, svg, jpeg, webp
                filename: title
            }
        };
        // Plot figure
        if (data && layout) {
            document.getElementById(divID).innerText="";
            document.getElementById(divID).innerHTML="";
            if (data.length > 0) {
                Plotly.newPlot(divID, data, layout, config);
            } else {
                var p_tag = document.createElement('p');
                p_tag.innerHTML = title + " is not available due to lack of data!!!";
                document.getElementById(divID).appendChild(p_tag);
            }
        }
    }

}
