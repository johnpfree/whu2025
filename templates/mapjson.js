var myjson = {MAP_JSON};
var jsonLayer = L.mapbox.featureLayer().addTo(map);
jsonLayer.setGeoJSON(myjson);
