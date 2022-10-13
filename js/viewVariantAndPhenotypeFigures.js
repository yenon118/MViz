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


function plotFigure(jsonObject, keyColumn, divID) {

    var data = [];
    var keys = Object.keys(jsonObject['Data']);

    if (jsonObject['IsFloat']){
        xAxisTitle = "Phenotype Measurement";
        yAxisTitle = keyColumn;
        // Update title
        title = keyColumn + " Box Plot";
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
        title = keyColumn + " Bar Plot";
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
                }
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