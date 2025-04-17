function tryGeolocate() {
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(success, error);
    }
  
    function success(position) {
      const lat = position.coords.latitude;
      const lon = position.coords.longitude;
      window.location.href = `/?lat=${lat}&lon=${lon}`;
    }
  
    function error() {
      alert("Geolokacije ni bilo mogoče pridobiti. Vnesi naslov ročno ali izberi najbližje območje.");
    }
  }

// Handle area selection dropdown
document.addEventListener('DOMContentLoaded', function () {
    const areaSelect = document.getElementById('area');
    if (areaSelect) {
      areaSelect.addEventListener('change', function () {
        const selectedArea = this.value;
        window.location.href = `/?area=${selectedArea}`;
      });
    }
});