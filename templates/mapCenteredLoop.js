var pline = [];
for (var i = 0; i < markers.length; i++) {
	pline.push([markers[i].geometry.coordinates[1], markers[i].geometry.coordinates[0]]);
}

// 1/2018 seems to work without the ready
// map.on('ready', function() {
// 	map.setView([{CENTER_LON}, {CENTER_LAT}], {ZOOM});
// })
map.setView([{CENTER_LON}, {CENTER_LAT}], {ZOOM});
console.log("setView", {CENTER_LON}, {CENTER_LAT}, {ZOOM});
