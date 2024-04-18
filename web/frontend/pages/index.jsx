import { TitleBar } from "@shopify/app-bridge-react";
import { Card, Page, Text, Button, LegacyCard } from "@shopify/polaris";
export default function HomePage() {
    return (
        <Page
            backAction={{ content: "Settings", url: "#" }}
            title="Home Page"
            primaryAction={<Button variant="primary">Save</Button>}
        >
            <LegacyCard title="Credit card" sectioned>
                <p>Credit card information</p>
            </LegacyCard>
        </Page>
    );
}
