import {
    LegacyCard,
    ResourceList,
    Avatar,
    ResourceItem,
    Text,
    LegacyFilters,
    TextField,
    Button,
    Tabs,
    Page,
    Link,
} from "@shopify/polaris";
import type { ResourceListProps } from "@shopify/polaris";
import React, { useState, useCallback } from "react";

export default function BlogPost() {
    return (
        <>
            <Page
                backAction={{ content: "Settings", url: "/blogPost" }}
                title="Manage Blog"
                primaryAction={
                    <Link url="/AddBlogPost">
                        <Button variant="primary">Add Blog</Button>
                    </Link>
                }
            >
                <LegacyCard>
                    <BlogTabs />
                </LegacyCard>
            </Page>
        </>
    );
}
function BlogPostList() {
    const [taggedWith, setTaggedWith] = useState<string | undefined>("VIP");
    const [queryValue, setQueryValue] = useState<string | undefined>(undefined);

    const handleTaggedWithChange = useCallback(
        (value: any) => setTaggedWith(value),
        []
    );
    const handleTaggedWithRemove = useCallback(
        () => setTaggedWith(undefined),
        []
    );
    const handleQueryValueRemove = useCallback(
        () => setQueryValue(undefined),
        []
    );
    const handleClearAll = useCallback(() => {
        handleTaggedWithRemove();
        handleQueryValueRemove();
    }, [handleQueryValueRemove, handleTaggedWithRemove]);

    const resourceName = {
        singular: "customer",
        plural: "customers",
    };

    const items = [
        {
            id: "108",
            url: "#",
            name: "Mae Jemison",
            location: "Decatur, USA",
        },
        {
            id: "208",
            url: "#",
            name: "Ellen Ochoa",
            location: "Los Angeles, USA",
        },
    ];

    const filters = [
        {
            key: "taggedWith1",
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
    ];

    const appliedFilters =
        taggedWith && !isEmpty(taggedWith)
            ? [
                  {
                      key: "taggedWith1",
                      label: disambiguateLabel("taggedWith1", taggedWith),
                      onRemove: handleTaggedWithRemove,
                  },
              ]
            : [];

    const filterControl = (
        <LegacyFilters
            queryValue={queryValue}
            filters={filters}
            onQueryChange={setQueryValue}
            onQueryClear={handleQueryValueRemove}
            onClearAll={handleClearAll}
        ></LegacyFilters>
    );

    return (
        <ResourceList
            resourceName={resourceName}
            items={items}
            renderItem={renderItem}
            filterControl={filterControl}
        />
    );

    function renderItem(item: (typeof items)[number]) {
        const { id, url, name, location } = item;
        const media = <Avatar customer size="md" name={name} />;

        return (
            <ResourceItem id={id} url={url} media={media}>
                <Text variant="bodyMd" fontWeight="bold" as="h3">
                    {name}
                </Text>
                <div>{location}</div>
                <div>Azozoakndkajdsnkj</div>
            </ResourceItem>
        );
    }

    function disambiguateLabel(key: string, value: string): string {
        switch (key) {
            case "taggedWith1":
                return `Tagged with ${value}`;
            default:
                return value;
        }
    }

    function isEmpty(value: string): boolean {
        if (Array.isArray(value)) {
            return value.length === 0;
        } else {
            return value === "" || value == null;
        }
    }
}
function BlogTabs() {
    const [selected, setSelected] = useState(0);

    const handleTabChange = useCallback(
        (selectedTabIndex: number) => setSelected(selectedTabIndex),
        []
    );

    const tabs = [
        {
            id: "all-customers-1",
            content: "All",
            accessibilityLabel: "All customers",
            panelID: "all-customers-content-1",
        },
    ];

    return (
        <Tabs tabs={tabs} selected={selected} onSelect={handleTabChange}>
            <LegacyCard.Section>
                {/* <p>Tab {selected} selected</p> */}
                <BlogPostList />
            </LegacyCard.Section>
        </Tabs>
    );
}
