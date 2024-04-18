import "../assets/style.css";
import {
    Page,
    Modal,
    Frame,
    Badge,
    Form,
    FormLayout,
    DropZone,
    Checkbox,
    LegacyStack,
    TextField,
    Button,
    Label,
} from "@shopify/polaris";
import React, { useState, useCallback } from "react";
import FilterTable from "../components/FilterTable";
import MultipleTags from "../components/MultipleCheckBox";
// import AddFilterModal from "../components/AddFilterModal";

export default function Filters() {
    const [isModalOpen, setIsModalOpen] = useState(false);
    const handleModalToggle = useCallback(() => {
        setIsModalOpen((prev) => !prev);
    }, []);

    return (
        <Page
            backAction={{ content: "Settings", url: "#" }}
            title="Filters Page"
            primaryAction={
                <Button variant="primary" onClick={handleModalToggle}>
                    Add Filters
                </Button>
            }
        >
            <AddFilterModal isOpen={isModalOpen} onClose={handleModalToggle} />
            <FilterTable />
        </Page>
    );
}

function AddFilterModal({ isOpen, onClose }) {
    const CURRENT_PAGE = "current_page";
    const ALL_CUSTOMERS = "all_customers";
    const SELECTED_CUSTOMERS = "selected_customers";
    const CSV_EXCEL = "csv_excel";
    const CSV_PLAIN = "csv_plain";

    const handleSelectedExport = useCallback((value) => {
        console.log(value);
    }, []);

    const handleSelectedExportAs = useCallback((value) => {
        console.log(value);
    }, []);
    const [newsletter, setNewsletter] = useState(false);
    const [email, setEmail] = useState("");

    const handleSubmit = useCallback(() => {
        setEmail("");
        setNewsletter(false);
    }, []);

    const handleNewsLetterChange = useCallback(
        (value) => setNewsletter(value),
        []
    );

    const handleEmailChange = useCallback((value) => setEmail(value), []);

    return (
        <Modal
            open={isOpen}
            onClose={onClose}
            title="Add Filter"
            primaryAction={{
                content: "Create",
                onAction: onClose,
            }}
            secondaryActions={[
                {
                    content: "Cancel",
                    onAction: onClose,
                },
            ]}
        >
            <Modal.Section>
                <LegacyStack vertical>
                    {" "}
                    <Form onSubmit={handleSubmit}>
                        <FormLayout>
                            <TextField
                                value={email}
                                onChange={handleEmailChange}
                                label="Filter Name"
                                type="email"
                                autoComplete="email"
                            />
                            <MultipleTags />
                            {/* <Button submit>Create Filter</Button> */}
                        </FormLayout>
                    </Form>
                </LegacyStack>
            </Modal.Section>
        </Modal>
    );
}
