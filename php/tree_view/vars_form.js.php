        <script>
            var max_rel_no = <?= count($parent_relationships)-1 ?>;

            function addRelationshipForm() {
                var relationship0_elem = document.getElementById('relationship_template');
                var new_elem = document.createElement('div');
                new_elem.classList.add('relationship');
                max_rel_no++;
                var new_html = relationship0_elem.innerHTML
                                .replace(/0/g, max_rel_no.toString());
                new_elem.innerHTML = new_html;

                var form = document.getElementById('vars_form');
                var add_rel_link = document.getElementById('add_relationship_link');
                form.insertBefore(new_elem, add_rel_link);
            }

            function getOptionalFieldsElem(header_elem) {
                console.log('header_elem', header_elem);
                var parent = header_elem.parentNode;
                console.log('parent', parent);
                var fields = parent.getElementsByClassName('optional_fields')[0];
                console.log('fields', fields);
                return fields;
            }

            function fieldsHasData(fields) {
                // #todo prevent people from accidentally
                // forgetting about their optional fields
                return false;
            }

            function showOptionalFields(header_elem) {
                var fields = getOptionalFieldsElem(header_elem);
                if (fields.classList.contains('open')) {
                    fields.classList.remove("open");
                    if (fieldsHasData(fields)) {
                        header_elem.classList.add("has_data");
                    }
                }
                else {
                    fields.classList.add("open");
                }
            }
        </script>

