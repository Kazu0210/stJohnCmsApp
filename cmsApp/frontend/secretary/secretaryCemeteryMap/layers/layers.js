var wms_layers = [];


        var lyr_1_0 = new ol.layer.Tile({
            'title': '1',
            'opacity': 1.000000,
            
            
            source: new ol.source.XYZ({
            attributions: ' ',
                url: 'http://mt0.google.com/vt/lyrs=s&hl=en&x={x}&y={y}&z={z}'
            })
        });
var format_nondescriptbuildings_1 = new ol.format.GeoJSON();
var features_nondescriptbuildings_1 = format_nondescriptbuildings_1.readFeatures(json_nondescriptbuildings_1, 
            {dataProjection: 'EPSG:4326', featureProjection: 'EPSG:3857'});
var jsonSource_nondescriptbuildings_1 = new ol.source.Vector({
    attributions: ' ',
});
jsonSource_nondescriptbuildings_1.addFeatures(features_nondescriptbuildings_1);
var lyr_nondescriptbuildings_1 = new ol.layer.Vector({
                declutter: false,
                source:jsonSource_nondescriptbuildings_1, 
                style: style_nondescriptbuildings_1,
                popuplayertitle: 'nondescript buildings',
                interactive: false,
                title: '<img src="styles/legend/nondescriptbuildings_1.png" /> nondescript buildings'
            });
var format_geo_2 = new ol.format.GeoJSON();
var features_geo_2 = format_geo_2.readFeatures(json_geo_2, 
            {dataProjection: 'EPSG:4326', featureProjection: 'EPSG:3857'});
var jsonSource_geo_2 = new ol.source.Vector({
    attributions: ' ',
});
jsonSource_geo_2.addFeatures(features_geo_2);
var lyr_geo_2 = new ol.layer.Vector({
                declutter: false,
                source:jsonSource_geo_2, 
                style: style_geo_2,
                popuplayertitle: 'geo',
                interactive: true,
    title: 'geo<br />\
    <img src="styles/legend/geo_2_0.png" /> <br />\
    <img src="styles/legend/geo_2_1.png" /> <br />\
    <img src="styles/legend/geo_2_2.png" /> <br />\
    <img src="styles/legend/geo_2_3.png" /> <br />' });

lyr_1_0.setVisible(true);lyr_nondescriptbuildings_1.setVisible(true);lyr_geo_2.setVisible(true);
var layersList = [lyr_1_0,lyr_nondescriptbuildings_1,lyr_geo_2];
lyr_nondescriptbuildings_1.set('fieldAliases', {'label': 'label', 'auxiliary_storage_labeling_show': 'auxiliary_storage_labeling_show', });
lyr_geo_2.set('fieldAliases', {'lotId': 'lotId', 'userId': 'userId', 'area': 'area', 'block': 'block', 'rowNumber': 'rowNumber', 'lotNumber': 'lotNumber', 'type': 'type', 'lotTypeId': 'lotTypeId', 'buryDepth': 'buryDepth', 'status': 'status', 'createdAt': 'createdAt', 'updatedAt': 'updatedAt', });
lyr_nondescriptbuildings_1.set('fieldImages', {'label': 'TextEdit', 'auxiliary_storage_labeling_show': 'Hidden', });
lyr_geo_2.set('fieldImages', {'lotId': 'TextEdit', 'userId': 'TextEdit', 'area': 'TextEdit', 'block': 'TextEdit', 'rowNumber': 'TextEdit', 'lotNumber': 'TextEdit', 'type': 'TextEdit', 'lotTypeId': 'Range', 'buryDepth': 'TextEdit', 'status': 'TextEdit', 'createdAt': 'DateTime', 'updatedAt': 'DateTime', });
lyr_nondescriptbuildings_1.set('fieldLabels', {'label': 'header label - always visible', });
lyr_geo_2.set('fieldLabels', {'lotId': 'header label - visible with data', 'userId': 'header label - visible with data', 'area': 'header label - visible with data', 'block': 'header label - visible with data', 'rowNumber': 'header label - visible with data', 'lotNumber': 'header label - visible with data', 'type': 'header label - visible with data', 'lotTypeId': 'no label', 'buryDepth': 'header label - visible with data', 'status': 'header label - visible with data', 'createdAt': 'no label', 'updatedAt': 'no label', });
lyr_geo_2.on('precompose', function(evt) {
    evt.context.globalCompositeOperation = 'normal';
});