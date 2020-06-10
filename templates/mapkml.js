
var customLayer = L.geoJson(null, {
  filter: function(geoJsonFeature) {
    return geoJsonFeature.geometry.type !== 'Point';    // filter out Points.
  }
});

var filename = '{KML_FILE}.kml';		// ka-ching! 
console.log('file', filename);
var runLayer = omnivore.kml(filename, null, customLayer)
    .on('ready', function() {
      this.eachLayer(function (layer) {
				if (layer.feature.geometry.type == 'LineString') {
            layer.setStyle({
              // color: '#535900',
              color: '#A43500',
              weight: 5
            });
					}
      });
    }).addTo(map);

$.ajax({												// check of a second file exists, add it of so.
	url: '{KML_FILE}1.kml',
	success: function(data){
		filename = '{KML_FILE}1.kml';		// ka-ching! 
		console.log('second file', filename);
		runLayer = omnivore.kml(filename, null, customLayer)
				.on('ready', function() {
					this.eachLayer(function (layer) {
						if (layer.feature.geometry.type == 'LineString') {
								layer.setStyle({
									color: '#A43500',
									weight: 5
								});
							}
					});
				}).addTo(map);
	},
	error: function(data){},
})
