<?php
            { # inline js
?>
    <script>

    // GLOBALS

    var lastClickedElem;
    var backlinkJoinTable;

    // keep track of table joins
    // for color choice if nothing else
    var joinNum = 0;


	function nthCol(n) {
        //console.log('nthCol', n);
        var n_plus_1 = (parseInt(n)+1).toString();
		return $('#query_table tr > *:nth-child('
            + n_plus_1 +
        ')');
	}

	function hideCol(n) {
		if (n > 0) {
			nthCol(n).hide();
			setShadowingCol(n-1);
		}
		else {
			alert("Can't hide 1st column");
		}
	}

	function showCol(n) {
		return nthCol(n).show();
	}

	function nthRow(n) {
        var n_plus_1 = (parseInt(n)+1).toString();
		return $('#query_table tr:nth-child('
			+ n_plus_1 +
		')');
	}

	function hideRow(n) {
		if (n > 0) {
			nthRow(n).hide();
			setShadowingRow(n-1);
		}
		else {
			alert("Can't hide 1st row");
		}
	}

	function showRow(n) {
		return nthRow(n).show();
	}

	function unfoldColsFrom(n) {
		var col = nthCol(n);
		col.nextUntil(':visible')
			.show()
			.removeClass('shadowing');
		unsetShadowingCol(n);
	}

	function unfoldRowsFrom(n) {
		var row = nthRow(n);
		row.nextUntil(':visible')
			.show()
			.removeClass('shadowing');
		unsetShadowingRow(n);
	}


	function setShadowingCol(n) {
		nthCol(n).addClass('shadowing'); //.css('background','#eeefee')
	}
	function unsetShadowingCol(n) {
		nthCol(n).removeClass('shadowing'); //.css('background','initial')
	}
	function setShadowingRow(n) {
		nthRow(n).addClass('shadowing'); //.css('background','#eeefee')
	}
	function unsetShadowingRow(n) {
		nthRow(n).removeClass('shadowing'); //.css('background','initial')
	}


    // split the row into `num_rows` rows
    // don't use a rowspan - it's simpler just to duplicate the id_field
        // might not look quite as cool but much simpler to code
        // also allows for tabular copy-and-paste into unix-style programs
    function splitRow($row, num_rows, mark_odd_row) {
        console.log('splitRow, num_rows =', num_rows);

        var $rows = [$row];

        // classes on the original (top) row
        // (even if we don't actually add any rows)
        $row.addClass('has-extra-rows');
        if (mark_odd_row) {
            $row.addClass('odd-row');
        }

        if (num_rows > 1) {

            var $prev_row = $row;
            console.log('adding new rows');

            // add rows
            for (var rowN = 1; rowN < num_rows; rowN++) {
                console.log('adding new extra row');

                var $new_row = $row.clone();
                $new_row.addClass('extra-row');
                if (mark_odd_row) {
                    $new_row.addClass('odd-row');
                }

                $rows.push($new_row);      // add to return array
                $prev_row.after($new_row); // add to DOM
                $prev_row = $new_row;
            }
        }
        else {
            console.log('no extra rows to add!');
        }

        return $rows;
    }

    function extraRowsUnder($row) {
        return $row.nextUntil(':not(.extra-row)');
    }

    function rowOf(elem) {
        return $(elem).closest('tr');
    }

    // jQuery allows handling multiple elements the same as 1
    rowsOf = rowOf;

    // undo the split created by splitRow
    function unsplitRow($row) {
        // remove tr.extra-row's underneath
        var extra_rows = extraRowsUnder($row);
        extra_rows.remove();
        $row.children('td');
        $row.removeClass('has-extra-rows')
            .removeClass('odd-row')
            ;
    }

    // unsplitRow for each row of each cell in cells
    function unsplitRows($cells) {
        $cells.each(function(idx,elem) {
            var $row = rowOf(elem);
            unsplitRow($row)
        });
    }

    // given a row, split it into multiple rows that go alongside it
    // for 1-to-many relationship
    function addExtraRowsBelowRow($row, new_rows, col_no, mark_odd_row) {
        var num_rows = new_rows.length;
        // add the needed rows
        var $rows = splitRow($row, num_rows, mark_odd_row);

        for (var rowN = 0; rowN < num_rows; rowN++) {
            // add to top row - some td's already present
            // must insert after the correct <td>
            var this_row = $rows[rowN];
            var first_elem = this_row.find('td').eq(col_no);
            var new_content = new_rows[rowN];
            //console.log('new_content', new_content);
            first_elem.after(new_content);
            //console.log('appended');
        }
    }

    function selectText(elem) {
        if (document.selection) {
            var range = document.body.createTextRange();
            range.moveToElementText(elem);
            range.select();
        }
        else if (window.getSelection) {
            var range = document.createRange();
            range.selectNode(elem);
            window.getSelection().addRange(range);
        }
    }

    </script>
<?php
            }
