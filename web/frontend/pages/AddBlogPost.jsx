import React, { useState, useCallback } from "react";
import {
    LegacyCard,
    Page,
    Layout,
    Button,
    TextField,
    Select,
    Link,
    Thumbnail,
    LegacyStack,
    ButtonGroup,
    DropZone,
    Form,
    ResourceItem,
    List,
    Checkbox,
    FormLayout,
    Modal,
    Text,
    ResourceList,
    Label,
    Avatar,
} from "@shopify/polaris";

import "../assets/style.css";
import { NoteIcon } from "@shopify/polaris-icons";
import { PlusIcon } from "@shopify/polaris-icons";
import { CKEditor } from "@ckeditor/ckeditor5-react";
import ClassicEditor from "@ckeditor/ckeditor5-build-classic";
export default function AddBlogPost() {
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [selectedProducts, setSelectedProducts] = useState([]);
    const [toolsAndAccessories, setToolsAndAccessories] = useState([]);
    const [newToolName, setNewToolName] = useState("");
    const [newToolPrice, setNewToolPrice] = useState("");
    const [showInputFields, setShowInputFields] = useState(false);
    const [instructions, setInstructions] = useState([]);

    const handleAddInstruction = () => {
        // Add a new instruction object to the instructions array
        setInstructions([...instructions, { text: "" }]);
    };

    const handleInstructionChange = (value, index) => {
        // Update the specific instruction at index
        const newInstructions = instructions.map((instr, idx) => {
            if (idx === index) {
                return { ...instr, text: value };
            }
            return instr;
        });
        setInstructions(newInstructions);
    };
    const handleRemoveInstruction = (index) => {
        // Remove the instruction at the specified index
        const newInstructions = instructions.filter((_, idx) => idx !== index);
        setInstructions(newInstructions);
    };
    const handleAddTool = () => {
        if (showInputFields && newToolName && newToolPrice) {
            const newTool = { name: newToolName, price: newToolPrice };
            setToolsAndAccessories([...toolsAndAccessories, newTool]);
            setNewToolName("");
            setNewToolPrice("");
            setShowInputFields(false); // Hide fields again after adding a tool
        } else {
            setShowInputFields(true); // Show fields when button is clicked
        }
    };

    const handleGridiantChange = (index, field, value) => {
        setSelectedProducts((prevSelected) => {
            const updatedProducts = [...prevSelected];
            updatedProducts[index][field] = value;
            return updatedProducts;
        });
    };
    const handleProductSelect = (product) => {
        const isProductSelected = selectedProducts.some(
            (item) => item.id === product.id
        );

        if (isProductSelected) {
            setSelectedProducts((prevSelected) =>
                prevSelected.filter((item) => item.id !== product.id)
            );
        } else {
            // Include gridiant data with the selected product
            const selectedProductWithGridiant = {
                ...product,
                price: "", // Add price data
                grams: "", // Add grams data
                percentage: "", // Add percentage data
                phase: "", // Add phase data
            };
            setSelectedProducts((prevSelected) => [
                ...prevSelected,
                selectedProductWithGridiant,
            ]);
        }
    };

    const handleModalToggle = useCallback(() => {
        setIsModalOpen((prev) => !prev);
    }, []);
    const [formData, setFormData] = useState({
        title: "",
        shortDescription1: "",
        shortDescription2: "",
        file: null,
        // selectValue: "kg",
        selected: "today",
        value: "1",
    });
    const [selectedOption, setSelectedOption] = useState(null);

    const handleOptionChange = (option) => {
        setSelectedOption(option === selectedOption ? null : option);
    };
    const handleInputChange = useCallback(
        (field, value) => {
            setFormData((prevFormData) => ({
                ...prevFormData,
                [field]: value,
            }));
        },
        [setFormData]
    );

    // Function to handle file upload in DropZone
    const handleDropZoneDrop = useCallback(
        (_dropFiles, acceptedFiles, _rejectedFiles) => {
            setFormData((prevFormData) => ({
                ...prevFormData,
                file: acceptedFiles[0],
            }));
        },
        [setFormData]
    );

    // Function to handle form submission
    const handleFormSubmit = () => {
        // Handle form submission logic here, e.g., send data to backend
        console.log("Form Data:", formData);
    };

    // Options for Select component
    const options = [
        { label: "Beginner", value: "Beginner" },
        { label: "intermediate", value: "intermediate" },
        { label: "advanced", value: "advanced" },
    ];

    // Valid image types for Thumbnail
    const validImageTypes = ["image/gif", "image/jpeg", "image/png"];

    // Conditional rendering for file upload and uploaded file
    const fileUpload = !formData.file && <DropZone.FileUpload />;
    const uploadedFile = formData.file && (
        <LegacyStack>
            <Thumbnail
                size="small"
                alt={formData.file.name}
                source={
                    validImageTypes.includes(formData.file.type)
                        ? window.URL.createObjectURL(formData.file)
                        : "adD iMAGE"
                }
            />
            <div>
                {formData.file.name}
                <p>{formData.file.size} byte</p>
            </div>
        </LegacyStack>
    );

    return (
        <Page
            backAction={{ content: "Settings", url: "/blogPost" }}
            title="Add Blog Post"
            // primaryAction={
            //     <Link variant="primary" url="/tab2">
            //         <Button variant="primary">Add Blog</Button>
            //     </Link>
            // }
        >
            <Form onSubmit={handleFormSubmit}>
                <Layout>
                    <Layout.Section>
                        <LegacyCard sectioned>
                            <div className="flex gap-3 flex-col">
                                <TextField
                                    label="Title"
                                    placeholder="e.g Blog About your Latest Products and Details"
                                    autoComplete="off"
                                    value={formData.title}
                                    onChange={(value) =>
                                        handleInputChange("title", value)
                                    }
                                />
                                <Label>Short Description</Label>

                                <CKEditor
                                    editor={ClassicEditor}
                                    data="<p></p>"
                                    onInit={(editor) => {}}
                                    config={{
                                        toolbar: [
                                            "selectAll",
                                            "undo",
                                            "redo",
                                            "bold",
                                            "italic",
                                            "blockQuote",
                                            "ckfinder",
                                            "imageTextAlternative",
                                            "imageUpload",
                                            "heading",
                                            "imageStyle:full",
                                            "imageStyle:side",
                                            "indent",
                                            "outdent",
                                            "link",
                                            "numberedList",
                                            "bulletedList",
                                            "mediaEmbed",
                                            "insertTable",
                                            "tableColumn",
                                            "tableRow",
                                            "mergeTableCells",
                                            "fontBackgroundColor",
                                            "fontColor",
                                        ],
                                    }}
                                    onChange={(value) =>
                                        handleInputChange(
                                            "shortDescription2",
                                            value
                                        )
                                    }
                                    value={formData.shortDescription1}
                                    // onBlur={(event, editor) => {
                                    //   console.log("Blur.", editor);
                                    //   //console.log(Array.from(editor.ui.componentFactory.names()));
                                    // }}
                                    // onFocus={(event, editor) => {
                                    //   console.log("Focus.", editor);
                                    // }}
                                />
                            </div>
                        </LegacyCard>
                        <LegacyCard sectioned>
                            <div className="flex gap-3 flex-col">
                                <Label>Excerpt</Label>
                                <CKEditor
                                    editor={ClassicEditor}
                                    data="<p></p>"
                                    onInit={(editor) => {}}
                                    config={{
                                        toolbar: [
                                            "selectAll",
                                            "undo",
                                            "redo",
                                            "bold",
                                            "italic",
                                            "blockQuote",
                                            "ckfinder",
                                            "imageTextAlternative",
                                            "imageUpload",
                                            "heading",
                                            "imageStyle:full",
                                            "imageStyle:side",
                                            "indent",
                                            "outdent",
                                            "link",
                                            "numberedList",
                                            "bulletedList",
                                            "mediaEmbed",
                                            "insertTable",
                                            "tableColumn",
                                            "tableRow",
                                            "mergeTableCells",
                                            "fontBackgroundColor",
                                            "fontColor",
                                        ],
                                    }}
                                    onChange={(value) =>
                                        handleInputChange(
                                            "shortDescription2",
                                            value
                                        )
                                    }
                                    value={formData.shortDescription2}
                                    // onBlur={(event, editor) => {
                                    //   console.log("Blur.", editor);
                                    //   //console.log(Array.from(editor.ui.componentFactory.names()));
                                    // }}
                                    // onFocus={(event, editor) => {
                                    //   console.log("Focus.", editor);
                                    // }}
                                />
                                {/* <TextField
                                    label="Excerpt"
                                    placeholder="e.g Blog About your Latest Products and Details"
                                    autoComplete="off"
                                    multiline={12}
                                    value={formData.shortDescription2}
                                    onChange={(value) =>
                                        handleInputChange(
                                            "shortDescription2",
                                            value
                                        )
                                    }
                                /> */}
                            </div>
                        </LegacyCard>
                        <LegacyCard sectioned title="Ingredients">
                            {selectedProducts.map((product, index) => (
                                <div
                                    key={product.id}
                                    className="flex gap-3 !justify-between flex-row border-y py-2"
                                >
                                    <p className="text-left">{product.name}</p>

                                    <div className="w-3/5 flex gap-3 flex-row">
                                        <TextField
                                            label={index === 0 && "Price"}
                                            autoComplete="off"
                                            type="number"
                                            value={product.price}
                                            onChange={(value) =>
                                                handleGridiantChange(
                                                    index,
                                                    "price",
                                                    value
                                                )
                                            }
                                        />
                                        <TextField
                                            label={index === 0 && "Percentage"}
                                            autoComplete="off"
                                            type="number"
                                            value={product.grams}
                                            onChange={(value) =>
                                                handleGridiantChange(
                                                    index,
                                                    "grams",
                                                    value
                                                )
                                            }
                                        />
                                        <TextField
                                            label={index === 0 && "Grams"}
                                            autoComplete="off"
                                            type="number"
                                            value={product.percentage}
                                            onChange={(value) =>
                                                handleGridiantChange(
                                                    index,
                                                    "percentage",
                                                    value
                                                )
                                            }
                                        />
                                        <TextField
                                            label={index === 0 && "Phase"}
                                            autoComplete="off"
                                            type="number"
                                            value={product.phase}
                                            onChange={(value) =>
                                                handleGridiantChange(
                                                    index,
                                                    "phase",
                                                    value
                                                )
                                            }
                                        />
                                    </div>
                                </div>
                            ))}

                            <div className="py-2 border-y my-2">
                                <Button
                                    variant="plain"
                                    onClick={handleModalToggle}
                                    icon={PlusIcon}
                                >
                                    Add Ingredients
                                </Button>
                            </div>
                        </LegacyCard>
                        <LegacyCard sectioned title="Tools And Accessories">
                            {selectedProducts.map((product, index) => (
                                <div
                                    key={product.id}
                                    className="flex gap-3 !justify-between flex-row border-y py-2"
                                >
                                    <p className="text-left">{product.name}</p>

                                    <div className="w-3/5 flex gap-3 flex-row">
                                        <TextField
                                            label={index === 0 && "Price"}
                                            autoComplete="off"
                                            type="number"
                                            value={product.price}
                                            onChange={(value) =>
                                                handleGridiantChange(
                                                    index,
                                                    "price",
                                                    value
                                                )
                                            }
                                        />
                                    </div>
                                </div>
                            ))}

                            <div className="py-2 border-y my-2">
                                <Button
                                    variant="plain"
                                    onClick={handleModalToggle}
                                    icon={PlusIcon}
                                >
                                    Add Tools And Accessories
                                </Button>
                            </div>
                        </LegacyCard>
                        <LegacyCard sectioned title="Instructuion">
                            <div sectioned>
                                <div className="py-2 border-y my-2">
                                    <Button
                                        variant="plain"
                                        onClick={handleAddInstruction}
                                        icon={PlusIcon}
                                    >
                                        Add Instruction
                                    </Button>
                                </div>
                                {instructions.map((instruction, index) => (
                                    <div
                                        key={index}
                                        className=" flex flex-col !justify-start "
                                    >
                                        <Label>Instruction {index + 1}</Label>
                                        <div className="flex flex-row !justify-start w-full ">
                                            <TextField
                                                value={instruction.text}
                                                onChange={(value) =>
                                                    handleInstructionChange(
                                                        value,
                                                        index
                                                    )
                                                }
                                                autoComplete="off"
                                                className="w-full"
                                            />
                                            <Button
                                                plain
                                                onClick={() =>
                                                    handleRemoveInstruction(
                                                        index
                                                    )
                                                }
                                            >
                                                Remove
                                            </Button>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </LegacyCard>
                    </Layout.Section>
                    <Layout.Section variant="oneThird">
                        <LegacyCard title="Filters Visibilities" sectioned>
                            <div className="flex flex-col">
                                <Checkbox
                                    label="Visible"
                                    checked={selectedOption === "visible"}
                                    onChange={() =>
                                        handleOptionChange("visible")
                                    }
                                />
                                <Checkbox
                                    label="Hidden"
                                    checked={selectedOption === "hidden"}
                                    onChange={() =>
                                        handleOptionChange("hidden")
                                    }
                                />
                            </div>
                        </LegacyCard>

                        <LegacyCard title="Featured image" sectioned>
                            <DropZone
                                allowMultiple={false}
                                onDrop={handleDropZoneDrop}
                            >
                                {uploadedFile}
                                {fileUpload}
                            </DropZone>
                        </LegacyCard>

                        <LegacyCard title="Time">
                            <div className="p-3">
                                <TextField
                                    type="number"
                                    value={formData.textFieldValue}
                                    onChange={(value) =>
                                        handleInputChange(
                                            "textFieldValue",
                                            value
                                        )
                                    }
                                    autoComplete="off"
                                    // connectedLeft={
                                    //     <Select
                                    //         value={formData.selectValue}
                                    //         label="Weight unit"
                                    //         onChange={(value) =>
                                    //             handleInputChange(
                                    //                 "selectValue",
                                    //                 value
                                    //             )
                                    //         }
                                    //         labelHidden
                                    //         options={[
                                    //             "mint",
                                    //             "sec",
                                    //             "hour",
                                    //             "day",
                                    //         ]}
                                    //     />
                                    // }
                                />
                            </div>
                        </LegacyCard>
                        <LegacyCard title="Level">
                            <div className="p-3">
                                <Select
                                    options={options}
                                    onChange={(value) =>
                                        handleInputChange("selected", value)
                                    }
                                    value={formData.selected}
                                />
                            </div>
                        </LegacyCard>
                        <LegacyCard title="No of ingredients">
                            <div className="p-3">
                                <TextField
                                    type="number"
                                    value={formData.value}
                                    onChange={(value) =>
                                        handleInputChange("value", value)
                                    }
                                ></TextField>
                            </div>
                        </LegacyCard>
                    </Layout.Section>
                </Layout>

                <div className="flex justify-end py-3">
                    <ButtonGroup>
                        <Button variant="primary" tone="critical">
                            Delete Blog Post
                        </Button>
                        <Button variant="primary" submit>
                            Save
                        </Button>
                    </ButtonGroup>
                </div>
            </Form>
            <AddIngredientsModal
                isOpen={isModalOpen}
                onClose={handleModalToggle}
                onSelect={handleProductSelect}
                selectedProducts={selectedProducts}
            />
        </Page>
    );
}

function AddIngredientsModal({ isOpen, onClose, onSelect, selectedProducts }) {
    const [selectedItems, setSelectedItems] = useState([]);

    const resourceName = {
        singular: "product",
        plural: "products",
    };

    const products = [
        { id: 1, name: "Product 1" },
        { id: 2, name: "Product 2" },
        { id: 3, name: "Product 3" },
    ];

    const renderItem = (item) => {
        return (
            <ResourceItem
                id={item.id}
                accessibilityLabel={`Select ${item.name}`}
            >
                <Checkbox
                    label={item.name}
                    checked={selectedProducts.some(
                        (selectedItem) => selectedItem.id === item.id
                    )}
                    onChange={() => onSelect(item)}
                />
            </ResourceItem>
        );
    };

    const handleSelectionChange = (selectedItems) => {
        setSelectedItems(selectedItems);
    };

    return (
        <Modal
            open={isOpen}
            onClose={onClose}
            title="Add Ingredients"
            primaryAction={{ content: "Close", onAction: onClose }}
        >
            <Modal.Section>
                <ResourceList
                    resourceName={resourceName}
                    items={products}
                    renderItem={renderItem}
                    selectedItems={selectedItems}
                    onSelectionChange={handleSelectionChange}
                />
            </Modal.Section>
        </Modal>
    );
}
