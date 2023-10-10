document.getElementById("defaultOpen").click();
function openCity(cityName) {
    var i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("tabcontent");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }
    tablinks = document.getElementsByClassName("tablinks");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].classList.remove("active");
        if (tablinks[i].dataset.tabName == cityName)
            tablinks[i].classList.add("active");
    }
    document.getElementById(cityName).style.display = "block";

    if (cityName == "Chat") {
        document.getElementById("chat-box").scrollTop = document.getElementById("chat-box").scrollHeight;
    }
}