var bounds = L.latLngBounds(L.latLng(markers[0].geometry.coordinates[1], markers[0].geometry.coordinates[0]));
// console.log('pre', bounds.isValid(), bounds);
var pline = [];
for (var i = 0; i < markers.length; i++) {
	var pt = L.latLng(markers[i].geometry.coordinates[1], markers[i].geometry.coordinates[0]);
	bounds.extend(pt);
	pline.push([markers[i].geometry.coordinates[1], markers[i].geometry.coordinates[0]]);
}
// console.log('post', bounds.isValid(), bounds);
map.fitBounds(bounds);	
