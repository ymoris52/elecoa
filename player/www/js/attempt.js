window.onload = function() {
    var e_cid = window.document.getElementById("cid");
    var e_sid = window.document.getElementById("sid");
    var e_cnt = window.document.getElementById("cnt");
    var e_cpf = window.document.getElementById("cpf");
    var e_new = window.document.getElementsByName("res").item(0);
    var e_res = window.document.getElementsByName("res").item(1);
    var e_clr = window.document.getElementsByName("res").item(2);
    var e_str = window.document.getElementsByTagName("button").item(0);
    var e_upl = window.document.getElementsByTagName("button").item(1);
    e_cid.onchange = function() {
        var index = e_cid.selectedIndex - 1;
        if (index < 0) {
            e_cnt.innerHTML = "-";
            e_new.checked = true;
            e_new.disabled = true;
            e_res.disabled = true;
            e_clr.disabled = true;
            e_str.disabled = true;
            return;
        }
        e_cnt.innerHTML = attempts[index][0] + " time(s)";
        e_new.checked = true;
        e_new.disabled = false;
        e_res.disabled = !attempts[index][1];
        e_clr.disabled = attempts[index][0] == 0;
        e_str.disabled = false;

        e_sid.innerHTML = '<option value="" selected="selected">Defalt</option>';
        if (e_sid.options.length == 0) { // FIX IE Bug
            var opt = document.createElement('option');
            opt.text = "Default";
            opt.value = "";
            e_sid.add(opt);
        }
        for (var aid in choices[index]) {
            var opt = document.createElement('option');
            opt.text = choices[index][aid];
            opt.value = aid;
            try {
                e_sid.add(opt, null); // standards compliant; doesn't work in IE
            } catch(ex) {
                e_sid.add(opt); // IE only
            }
        }
    }
    e_new.onclick = function() {
        e_sid.disabled = false;
    }
    e_res.onclick = function() {
        e_sid.selectedIndex = 0;
        e_sid.disabled = true;
    }
    e_clr.onclick = function() {
        e_sid.disabled = false;
    }
    e_cpf.onchange = function() {
        if (e_cpf.value != '') {
            e_upl.disabled = false;
        }
    }
}
