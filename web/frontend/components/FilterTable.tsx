import {
    TextField,
    IndexTable,
    LegacyCard,
    IndexFilters,
    useSetIndexFiltersMode,
    useIndexResourceState,
    Text,
    ChoiceList,
    Button,
    ButtonGroup,
    RangeSlider,
    Badge,
    useBreakpoints,
} from "@shopify/polaris";
import "../assets/style.css";
import type { IndexFiltersProps, TabProps } from "@shopify/polaris";
import React, { useState, useCallback } from "react";

export default function FilterTable() {
    const sleep = (ms: number) =>
        new Promise((resolve) => setTimeout(resolve, ms));
    const [itemStrings, setItemStrings] = useState(["All", "Active", "Draft"]);
    const deleteView = (index: number) => {
        const newItemStrings = [...itemStrings];
        newItemStrings.splice(index, 1);
        setItemStrings(newItemStrings);
        setSelected(0);
    };

    const duplicateView = async (name: string) => {
        setItemStrings([...itemStrings, name]);
        setSelected(itemStrings.length);
        await sleep(1);
        return true;
    };

    const tabs: TabProps[] = itemStrings.map((item, index) => ({
        content: item,
        index,
        onAction: () => {},
        id: `${item}-${index}`,
        isLocked: index === 0,
        actions:
            index === 0
                ? []
                : [
                      {
                          type: "rename",
                          onAction: () => {},
                          onPrimaryAction: async (
                              value: string
                          ): Promise<boolean> => {
                              const newItemsStrings = tabs.map((item, idx) => {
                                  if (idx === index) {
                                      return value;
                                  }
                                  return item.content;
                              });
                              await sleep(1);
                              setItemStrings(newItemsStrings);
                              return true;
                          },
                      },
                      {
                          type: "duplicate",
                          onPrimaryAction: async (
                              value: string
                          ): Promise<boolean> => {
                              await sleep(1);
                              duplicateView(value);
                              return true;
                          },
                      },
                      {
                          type: "edit",
                      },
                      {
                          type: "delete",
                          onPrimaryAction: async () => {
                              await sleep(1);
                              deleteView(index);
                              return true;
                          },
                      },
                  ],
    }));
    const [selected, setSelected] = useState(0);
    const onCreateNewView = async (value: string) => {
        await sleep(500);
        setItemStrings([...itemStrings, value]);
        setSelected(itemStrings.length);
        return true;
    };
    const sortOptions: IndexFiltersProps["sortOptions"] = [
        { label: "Order", value: "order asc", directionLabel: "Ascending" },
        { label: "Order", value: "order desc", directionLabel: "Descending" },
        { label: "Customer", value: "customer asc", directionLabel: "A-Z" },
        { label: "Customer", value: "customer desc", directionLabel: "Z-A" },
        { label: "Date", value: "date asc", directionLabel: "A-Z" },
        { label: "Date", value: "date desc", directionLabel: "Z-A" },
        { label: "Total", value: "total asc", directionLabel: "Ascending" },
        { label: "Total", value: "total desc", directionLabel: "Descending" },
    ];
    const [sortSelected, setSortSelected] = useState(["order asc"]);
    const { mode, setMode } = useSetIndexFiltersMode();
    const onHandleCancel = () => {};

    const onHandleSave = async () => {
        await sleep(1);
        return true;
    };

    const primaryAction: IndexFiltersProps["primaryAction"] =
        selected === 0
            ? {
                  type: "save-as",
                  onAction: onCreateNewView,
                  disabled: false,
                  loading: false,
              }
            : {
                  type: "save",
                  onAction: onHandleSave,
                  disabled: false,
                  loading: false,
              };
    const [accountStatus, setAccountStatus] = useState<string[] | undefined>(
        undefined
    );
    const [moneySpent, setMoneySpent] = useState<[number, number] | undefined>(
        undefined
    );
    const [taggedWith, setTaggedWith] = useState("");
    const [queryValue, setQueryValue] = useState("");

    const handleAccountStatusChange = useCallback(
        (value: string[]) => setAccountStatus(value),
        []
    );
    const handleMoneySpentChange = useCallback(
        (value: [number, number]) => setMoneySpent(value),
        []
    );
    const handleTaggedWithChange = useCallback(
        (value: string) => setTaggedWith(value),
        []
    );
    const handleFiltersQueryChange = useCallback(
        (value: string) => setQueryValue(value),
        []
    );
    const handleAccountStatusRemove = useCallback(
        () => setAccountStatus(undefined),
        []
    );
    const handleMoneySpentRemove = useCallback(
        () => setMoneySpent(undefined),
        []
    );
    const handleTaggedWithRemove = useCallback(() => setTaggedWith(""), []);
    const handleQueryValueRemove = useCallback(() => setQueryValue(""), []);
    const handleFiltersClearAll = useCallback(() => {
        handleAccountStatusRemove();
        handleMoneySpentRemove();
        handleTaggedWithRemove();
        handleQueryValueRemove();
    }, [
        handleAccountStatusRemove,
        handleMoneySpentRemove,
        handleQueryValueRemove,
        handleTaggedWithRemove,
    ]);

    const filters = [
        {
            key: "accountStatus",
            label: "Account status",
            filter: (
                <ChoiceList
                    title="Account status"
                    titleHidden
                    choices={[
                        { label: "Enabled", value: "enabled" },
                        { label: "Not invited", value: "not invited" },
                        { label: "Invited", value: "invited" },
                        { label: "Declined", value: "declined" },
                    ]}
                    selected={accountStatus || []}
                    onChange={handleAccountStatusChange}
                    allowMultiple
                />
            ),
            shortcut: true,
        },
        {
            key: "taggedWith",
            label: "Tagged with",
            filter: (
                <TextField
                    label="Tagged with"
                    value={taggedWith}
                    onChange={handleTaggedWithChange}
                    autoComplete="off"
                    labelHidden
                />
            ),
            shortcut: true,
        },
        {
            key: "moneySpent",
            label: "Money spent",
            filter: (
                <RangeSlider
                    label="Money spent is between"
                    labelHidden
                    value={moneySpent || [0, 500]}
                    prefix="$"
                    output
                    min={0}
                    max={2000}
                    step={1}
                    onChange={handleMoneySpentChange}
                />
            ),
        },
    ];

    const appliedFilters: IndexFiltersProps["appliedFilters"] = [];
    if (accountStatus && !isEmpty(accountStatus)) {
        const key = "accountStatus";
        appliedFilters.push({
            key,
            label: disambiguateLabel(key, accountStatus),
            onRemove: handleAccountStatusRemove,
        });
    }
    if (moneySpent) {
        const key = "moneySpent";
        appliedFilters.push({
            key,
            label: disambiguateLabel(key, moneySpent),
            onRemove: handleMoneySpentRemove,
        });
    }
    if (!isEmpty(taggedWith)) {
        const key = "taggedWith";
        appliedFilters.push({
            key,
            label: disambiguateLabel(key, taggedWith),
            onRemove: handleTaggedWithRemove,
        });
    }

    const orders = [
        {
            id: "1020",
            order: (
                <Text as="span" variant="bodyMd" fontWeight="semibold">
                    Filter 1
                </Text>
            ),
        },
        {
            id: "1019",
            order: (
                <Text as="span" variant="bodyMd" fontWeight="semibold">
                    Filter 2
                </Text>
            ),
        },
        {
            id: "1018",
            order: (
                <Text as="span" variant="bodyMd" fontWeight="semibold">
                    Filter 3
                </Text>
            ),
        },
    ];
    const resourceName = {
        singular: "order",
        plural: "orders",
    };

    const { selectedResources, allResourcesSelected, handleSelectionChange } =
        useIndexResourceState(orders);

    const rowMarkup = orders.map(({ id, order }, index) => (
        <IndexTable.Row
            id={id}
            key={id}
            selected={selectedResources.includes(id)}
            position={index}
        >
            <IndexTable.Cell>
                <Text variant="bodyMd" fontWeight="bold" as="span">
                    {order}
                </Text>
            </IndexTable.Cell>
            <IndexTable.Cell>
                <label className="switch">
                    <input type="checkbox" />
                    <span className="slider round"></span>
                </label>
            </IndexTable.Cell>
            <IndexTable.Cell>
                <div className="buttonGroup">
                    <ButtonGroup>
                        <Button>Update</Button>
                        <Button variant="primary">Delete</Button>
                    </ButtonGroup>
                </div>
            </IndexTable.Cell>
        </IndexTable.Row>
    ));

    return (
        <LegacyCard>
            <IndexFilters
                sortOptions={sortOptions}
                sortSelected={sortSelected}
                queryValue={queryValue}
                queryPlaceholder="Searching in all"
                onQueryChange={handleFiltersQueryChange}
                onQueryClear={() => setQueryValue("")}
                onSort={setSortSelected}
                primaryAction={primaryAction}
                cancelAction={{
                    onAction: onHandleCancel,
                    disabled: false,
                    loading: false,
                }}
                tabs={tabs}
                selected={selected}
                onSelect={setSelected}
                canCreateNewView
                onCreateNewView={onCreateNewView}
                filters={filters}
                appliedFilters={appliedFilters}
                onClearAll={handleFiltersClearAll}
                mode={mode}
                setMode={setMode}
            />
            <IndexTable
                condensed={useBreakpoints().smDown}
                resourceName={resourceName}
                itemCount={orders.length}
                selectedItemsCount={
                    allResourcesSelected ? "All" : selectedResources.length
                }
                onSelectionChange={handleSelectionChange}
                headings={[
                    { title: "Filter Name" },
                    { title: "Status" },
                    { title: "" },
                ]}
                selectable={false}
            >
                {rowMarkup}
            </IndexTable>
        </LegacyCard>
    );

    function disambiguateLabel(key: string, value: string | any[]): string {
        switch (key) {
            case "moneySpent":
                return `Money spent is between $${value[0]} and $${value[1]}`;
            case "taggedWith":
                return `Tagged with ${value}`;
            case "accountStatus":
                return (value as string[])
                    .map((val) => `Customer ${val}`)
                    .join(", ");
            default:
                return value as string;
        }
    }

    function isEmpty(value: string | string[]): boolean {
        if (Array.isArray(value)) {
            return value.length === 0;
        } else {
            return value === "" || value == null;
        }
    }
}
