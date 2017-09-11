// #todo #fixme don't use orphan global vars
// in an include like this:
// maybe put it in a popup obj? popup.elem_w_popup_open
var elem_w_popup_open = null;

function closePopup() {
    var popup = document.getElementById('popup');
    if (popup) {
        popup.parentElement.removeChild(popup);
    }

    if (elem_w_popup_open) {
        elem_w_popup_open.classList.remove('focus_of_popup');
    }

    document.body.removeEventListener('click', closePopup);
}

function addPopupOption(popup_elem, name, callback) {
    var link = document.createElement('li');
    link.classList.add('non_link');
    link.innerHTML = name;
    link.addEventListener('click', callback);
    popup_elem.append(link);
}

// options = an array of {name: _, callback: _}
function openPopup(options, event, clicked_node) {
    closePopup();

    var popup = document.createElement('ul');
    popup.setAttribute('id', 'popup');

    for (var i=0; i<options.length; i++) {
        var option = options[i];
        addPopupOption(popup, option.name, option.callback);
    }

    document.body.appendChild(popup);
    document.body.addEventListener('click', closePopup);
    event.stopPropagation(); // avoid this click triggering closePopup

    // update style / position
    popup.style.position = 'absolute';
    mouse_x = event.layerX - 20;
    mouse_y = event.layerY - 2;
    popup.style.left = mouse_x + 'px';
    popup.style.top = mouse_y + 'px';

    elem_w_popup_open = clicked_node;
    elem_w_popup_open.classList.add('focus_of_popup');
}

