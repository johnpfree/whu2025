var bounds = L.latLngBounds(L.latLng(markers[0].geometry.coordinates[1], markers[0].geometry.coordinates[0]));
// console.log('pre', bounds.isValid(), bounds);
var pline = [];
for (var i = 0; i < markers.length; i++) {
	var pt = L.latLng(markers[i].geometry.coordinates[1], markers[i].geometry.coordinates[0]);
	bounds.extend(pt);
	// console.log(i, markers[i].properties.title);
	// console.log(i, pt);
	// console.log(i, bounds.isValid(), bounds);

	// collect coords for pline
	pline.push([markers[i].geometry.coordinates[1], markers[i].geometry.coordinates[0]]);
}
// console.log('post', bounds.isValid(), bounds);
map.fitBounds(bounds);	
